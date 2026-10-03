<?php

namespace Webkul\Teamwork\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Teamwork\Models\FollowUp;
use Webkul\Teamwork\Models\StageMilestone;
use Webkul\Teamwork\Models\StagePlaybook;
use Webkul\User\Models\User;

/**
 * Stage playbooks: the milestones of each pipeline stage, whether a lead may
 * move forward with them missing, and what the team gets when a lead arrives
 * in a stage (a follow-up, notices, the previous stage's pending items).
 */
class StagePlaybooks
{
    /**
     * Milestones and gate of each stage, loaded once per request.
     */
    protected array $stageMilestones = [];

    protected array $stageGates = [];

    public function __construct(
        protected MilestoneChecks $checks,
        protected Notifier $notifier,
        protected Followers $followers,
        protected TeamScope $teamScope,
    ) {}

    // ── Milestones ────────────────────────────────────────────────────────

    /**
     * Milestones of a stage with their state for one lead.
     *
     * @return Collection<int, object{milestone: StageMilestone, done: bool, automatic: bool, ticked: ?object}>
     */
    public function milestones(object $lead, ?int $stageId = null): Collection
    {
        $stageId ??= (int) $lead->lead_pipeline_stage_id;
        $milestones = $this->stageMilestones[$stageId] ??= StageMilestone::where('lead_pipeline_stage_id', $stageId)->orderBy('position')->get();

        $ticked = DB::table('teamwork_lead_milestones')
            ->leftJoin('users', 'users.id', '=', 'teamwork_lead_milestones.done_by')
            ->where('lead_id', $lead->id)
            ->whereIn('milestone_id', $milestones->pluck('id'))
            ->get(['milestone_id', 'done_at', 'note', 'users.name as user_name'])
            ->keyBy('milestone_id');

        return $milestones->map(function (StageMilestone $milestone) use ($lead, $ticked) {
            $automatic = $milestone->isAutomatic() && $this->checks->passes($milestone->check, $lead);

            return (object) [
                'milestone' => $milestone,
                'automatic' => $automatic,
                'ticked' => $ticked->get($milestone->id),
                'done' => $automatic || $ticked->has($milestone->id),
            ];
        });
    }

    /**
     * What stands between a lead and the target stage: the gate mode (the
     * strictest of the stages it leaves behind) and the required milestones
     * still missing. Moving back, or to "lost", is always free.
     *
     * @return array{mode: string, missing: array<int, array{stage: string, name: string}>}
     */
    public function gate(object $lead, int $targetStageId): array
    {
        $stages = DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $lead->lead_pipeline_id)->orderBy('sort_order')->get(['id', 'name', 'code', 'sort_order'])->keyBy('id');
        $current = $stages->get($lead->lead_pipeline_stage_id);
        $target = $stages->get($targetStageId);

        if (! $current || ! $target || $target->code === 'lost' || $target->id === $current->id || $target->sort_order < $current->sort_order) {
            return ['mode' => 'off', 'missing' => []];
        }

        $passed = $stages->filter(fn ($stage) => $stage->sort_order >= $current->sort_order && $stage->sort_order < $target->sort_order && $stage->code !== 'lost');
        $playbooks = StagePlaybook::whereIn('lead_pipeline_stage_id', $passed->keys())->pluck('gate', 'lead_pipeline_stage_id');

        $mode = 'off';
        $missing = [];

        foreach ($passed as $stage) {
            $gate = $playbooks[$stage->id] ?? 'warn';

            if ($gate === 'off') {
                continue;
            }

            $mode = $mode === 'block' || $gate === 'block' ? 'block' : 'warn';

            foreach ($this->milestones($lead, $stage->id) as $item) {
                if ($item->milestone->is_required && ! $item->done) {
                    $missing[] = ['stage' => $this->stageName($stage), 'name' => $item->milestone->name];
                }
            }
        }

        return ['mode' => $missing ? $mode : 'off', 'missing' => $missing];
    }

    /**
     * Compact progress for lists and kanban cards: done/total in the current
     * stage, and whether the lead is stuck behind a "block" gate.
     *
     * @return array{done: int, total: int, blocked: bool, missing: string}|null
     */
    public function summary(object $lead): ?array
    {
        try {
            $items = $this->milestones($lead);

            if ($items->isEmpty()) {
                return null;
            }

            $stageId = (int) $lead->lead_pipeline_stage_id;
            $gate = $this->stageGates[$stageId] ??= (string) (StagePlaybook::where('lead_pipeline_stage_id', $stageId)->value('gate') ?? 'warn');
            $pending = $items->reject->done;

            return [
                'done' => $items->count() - $pending->count(),
                'total' => $items->count(),
                'blocked' => $gate === 'block' && $pending->contains(fn ($item) => $item->milestone->is_required),
                'missing' => $pending->map(fn ($item) => $item->milestone->name)->implode(' · '),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function tick(int $leadId, int $milestoneId, bool $done, ?string $note = null): void
    {
        if ($done) {
            DB::table('teamwork_lead_milestones')->updateOrInsert(
                ['lead_id' => $leadId, 'milestone_id' => $milestoneId],
                ['done_by' => auth()->guard('user')->id(), 'done_at' => now(), 'note' => $note ? Str::limit($note, 300, '') : null]
            );
        } else {
            DB::table('teamwork_lead_milestones')->where('lead_id', $leadId)->where('milestone_id', $milestoneId)->delete();
        }

        $this->checks->forget($leadId);
    }

    // ── Stage changes ─────────────────────────────────────────────────────

    /**
     * A lead moved from one stage to another: close the old stage's
     * playbook tasks, carry its pending milestones over, open the new
     * stage's follow-up and tell whoever should know.
     */
    public function stageChanged(object $lead, int $fromStageId, int $toStageId): void
    {
        $from = StagePlaybook::forStage($fromStageId);
        $to = StagePlaybook::forStage($toStageId);
        $toStage = DB::table('lead_pipeline_stages')->where('id', $toStageId)->first(['id', 'name', 'code']);
        $fromStage = DB::table('lead_pipeline_stages')->where('id', $fromStageId)->first(['id', 'name', 'code']);
        $actorId = auth()->guard('user')->id();
        $ownerId = (int) $lead->user_id ?: null;
        $url = route('admin.leads.view', $lead->id, false);

        if ($from->close_previous) {
            FollowUp::where('source', 'like', "playbook:{$lead->id}:{$fromStageId}:%")
                ->where('status', FollowUp::STATUS_OPEN)
                ->update(['status' => FollowUp::STATUS_DONE, 'resolved_at' => now(), 'resolved_by' => $actorId, 'last_activity_at' => now()]);
        }

        // Pending items of the stage it left (only when moving forward, not to "lost").
        $forward = $toStage && $toStage->code !== 'lost' && $this->isForward($lead, $fromStageId, $toStageId);

        if ($from->carry_over && $forward && $ownerId) {
            $pending = $this->milestones($lead, $fromStageId)->reject->done->map(fn ($item) => $item->milestone->name)->values();

            if ($pending->isNotEmpty()) {
                $this->createTask($lead, $ownerId, "playbook:{$lead->id}:{$toStageId}:carry",
                    trans('teamwork::app.playbook.tasks.carry-over', ['stage' => $this->stageName($fromStage), 'lead' => $lead->title]),
                    '• '.$pending->implode("\n• "), 24, false, $url);
            }
        }

        if ($to->entry_task && $toStage) {
            $assignee = $this->assignee($to, $ownerId);

            if ($assignee) {
                $milestones = $this->milestones($lead, $toStageId)->map(fn ($item) => ($item->done ? '✓ ' : '○ ').$item->milestone->name.($item->milestone->is_required ? '' : ' ('.trans('teamwork::app.playbook.recommended').')'));
                $vars = [
                    'lead' => (string) $lead->title,
                    'stage' => $this->stageName($toStage),
                    'client' => (string) DB::table('persons')->where('id', $lead->person_id)->value('name'),
                ];

                $title = $to->entry_task_title
                    ? strtr($to->entry_task_title, ['{lead}' => $vars['lead'], '{stage}' => $vars['stage'], '{client}' => $vars['client']])
                    : trans('teamwork::app.playbook.tasks.entry', $vars);

                $this->createTask($lead, $assignee, "playbook:{$lead->id}:{$toStageId}:entry", $title, $milestones->implode("\n"), (int) $to->entry_task_hours ?: 24, false, $url);
            }
        }

        $notice = trans('teamwork::app.playbook.notices.moved', [
            'name' => auth()->guard('user')->user()?->name ?? trans('teamwork::app.playbook.system'),
            'lead' => $lead->title,
            'stage' => $toStage ? $this->stageName($toStage) : '',
        ]);

        if ($to->notify_owner && $ownerId) {
            $this->notifier->notify($ownerId, Notifier::RECORD_STAGE, $notice, null, $url);
        }

        if ($to->notify_master) {
            $this->notifier->notify($this->masters($lead->agency_id ?? null), Notifier::RECORD_STAGE, $notice, null, $url, $toStage?->code === 'won');
        }
    }

    /**
     * Open playbook tasks of a lead's current stage.
     */
    public function openTasks(object $lead): Collection
    {
        return FollowUp::with('assignee:id,name')
            ->where('source', 'like', "playbook:{$lead->id}:{$lead->lead_pipeline_stage_id}:%")
            ->where('status', FollowUp::STATUS_OPEN)
            ->orderBy('due_at')
            ->get();
    }

    public function isMaster(?User $user): bool
    {
        return $user && ($user->role?->permission_type === 'all' || $this->teamScope->isMasterAgent($user));
    }

    public function stageName($stage): string
    {
        foreach ([$stage->code ?? null, $stage->name ?? null] as $source) {
            $slug = Str::slug((string) $source, '_');

            foreach (["admin::insurance.pipeline_stages.general.{$slug}", "admin::app.pipeline_stages.general.{$slug}"] as $key) {
                if ($slug && trans()->has($key)) {
                    return trans($key);
                }
            }
        }

        return (string) ($stage->name ?? '');
    }

    protected function isForward(object $lead, int $fromStageId, int $toStageId): bool
    {
        $orders = DB::table('lead_pipeline_stages')->whereIn('id', [$fromStageId, $toStageId])->pluck('sort_order', 'id');

        return ($orders[$toStageId] ?? 0) > ($orders[$fromStageId] ?? 0);
    }

    protected function createTask(object $lead, int $assignee, string $source, string $title, ?string $note, int $hours, bool $urgent, string $url): void
    {
        // One per stage visit: entering again later replaces an open one.
        if (FollowUp::where('source', $source)->where('status', FollowUp::STATUS_OPEN)->exists()) {
            return;
        }

        $followUp = FollowUp::create([
            'agency_id' => $lead->agency_id ?? null,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'title' => Str::limit($title, 180, ''),
            'url' => $url,
            'source' => $source,
            'assigned_to' => $assignee,
            'created_by' => auth()->guard('user')->id() ?: $assignee,
            'priority' => $urgent ? FollowUp::PRIORITY_URGENT : FollowUp::PRIORITY_NORMAL,
            'note' => '🧭 '.$note,
            'due_at' => now()->addHours(max(1, $hours)),
            'status' => FollowUp::STATUS_OPEN,
            'last_activity_at' => now(),
        ]);

        $this->followers->follow($assignee, 'lead', (int) $lead->id, true);

        $this->notifier->notify($assignee, $urgent ? Notifier::FOLLOW_UP_URGENT : Notifier::FOLLOW_UP_ASSIGNED, $followUp->title, $note,
            route('admin.teamwork.follow_ups.show', $followUp->id, false), $urgent);
    }

    protected function assignee(StagePlaybook $playbook, ?int $ownerId): ?int
    {
        return match ($playbook->entry_assign) {
            'user' => $playbook->entry_user_id ?: $ownerId,
            'master' => $this->masters()[0] ?? $ownerId,
            default => $ownerId ?: ($this->masters()[0] ?? null),
        };
    }

    protected function masters(?int $agencyId = null): array
    {
        return User::with('role')
            ->when($agencyId, fn ($query) => $query->where('agency_id', $agencyId))
            ->where('status', 1)
            ->get()
            ->filter(fn ($user) => $this->isMaster($user))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
