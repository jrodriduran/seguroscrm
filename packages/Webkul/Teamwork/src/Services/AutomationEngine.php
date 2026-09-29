<?php

namespace Webkul\Teamwork\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Teamwork\Models\Automation;
use Webkul\Teamwork\Models\FollowUp;
use Webkul\User\Models\User;

/**
 * Runs team automations: event triggers (lead created, stage entered) from
 * listeners, and date triggers (renewal coming up, case overdue) from the
 * scheduled teamwork:run-automations command. Each automation acts at most
 * once per record and occasion (teamwork_automation_runs).
 */
class AutomationEngine
{
    public function __construct(
        protected BusinessHours $businessHours,
        protected Notifier $notifier,
        protected Followers $followers,
        protected TeamScope $teamScope,
        protected WorkQueue $workQueue,
    ) {}

    public function leadCreated($lead): void
    {
        foreach ($this->active('lead_created', $lead->agency_id ?? null) as $automation) {
            $this->run($automation, $this->leadContext($lead), 'lead:'.$lead->id);
        }

        // A new lead also "enters" its first stage.
        $this->stageEntered($lead);
    }

    public function stageEntered($lead): void
    {
        $stage = DB::table('lead_pipeline_stages')->where('id', $lead->lead_pipeline_stage_id)->first(['id', 'code']);

        if (! $stage) {
            return;
        }

        foreach ($this->active('stage_entered', $lead->agency_id ?? null) as $automation) {
            $matches = ($automation->condition('stage_id') && (int) $automation->condition('stage_id') === (int) $stage->id)
                || ($automation->condition('stage_code') && $automation->condition('stage_code') === $stage->code);

            if ($matches) {
                // Entering the same stage again later is a new occasion.
                $this->run($automation, $this->leadContext($lead), 'lead:'.$lead->id.':stage:'.$stage->id.':'.now()->format('YmdHi'));
            }
        }
    }

    /**
     * Date-based triggers. Returns how many actions were taken.
     */
    public function runScheduled(): int
    {
        $done = 0;

        foreach ($this->active('renewal_upcoming') as $automation) {
            $days = max(1, (int) $automation->condition('days_before', 60));

            $policies = DB::table('insurance_policies')
                ->whereNotNull('renewal_date')
                ->whereBetween('renewal_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
                ->when($automation->agency_id, fn ($query) => $query->where('agency_id', $automation->agency_id))
                ->limit(500)
                ->get(['id', 'lead_id', 'person_id', 'user_id', 'renewal_date']);

            foreach ($policies as $policy) {
                $context = $policy->lead_id && ($lead = DB::table('leads')->where('id', $policy->lead_id)->first())
                    ? $this->leadContext($lead)
                    : $this->personContext((int) $policy->person_id, $policy->user_id);

                if (! $context) {
                    continue;
                }

                $context['vars']['date'] = Carbon::parse($policy->renewal_date)->format('d/m/Y');

                $done += (int) $this->run($automation, $context, 'policy:'.$policy->id.':renewal:'.$policy->renewal_date);
            }
        }

        foreach ($this->active('case_overdue') as $automation) {
            $userIds = User::query()
                ->when($automation->agency_id, fn ($query) => $query->where('agency_id', $automation->agency_id))
                ->where('status', 1)
                ->pluck('id')
                ->all();

            foreach ($this->workQueue->openCases($userIds, 500)->where('state', WorkQueue::OVERDUE) as $case) {
                $lead = DB::table('leads')->where('id', $case->id)->first();
                $context = $this->leadContext($lead);
                $context['vars']['idle'] = $case->idle_label;

                // Once per case per day.
                $done += (int) $this->run($automation, $context, 'lead:'.$case->id.':overdue:'.now()->toDateString());
            }
        }

        return $done;
    }

    /**
     * Carry out one automation for one record. False if it already ran.
     */
    public function run(Automation $automation, array $context, string $runKey): bool
    {
        $inserted = DB::table('teamwork_automation_runs')->insertOrIgnore([
            'automation_id' => $automation->id,
            'run_key' => substr($runKey, 0, 120),
            'entity_type' => $context['entity_type'],
            'entity_id' => $context['entity_id'],
            'created_at' => now(),
        ]);

        if (! $inserted) {
            return false;
        }

        try {
            $recipients = $this->recipients($automation, $context);

            if (! $recipients) {
                return false;
            }

            $note = $this->fill((string) $automation->note_template, $context['vars']);
            $urgent = $automation->priority === FollowUp::PRIORITY_URGENT;
            $title = trans('teamwork::app.automations.notification', ['name' => $automation->name, 'record' => $context['title']]);

            if ($automation->action === 'notify') {
                $this->notifier->notify($recipients, Notifier::AUTOMATION, $title, $note, $context['url'], $urgent);

                return true;
            }

            $assignee = $recipients[0];

            $followUp = FollowUp::create([
                'agency_id' => $automation->agency_id,
                'entity_type' => $context['entity_type'],
                'entity_id' => $context['entity_id'],
                'title' => $context['title'],
                'url' => $context['url'],
                'assigned_to' => $assignee,
                'created_by' => $assignee,
                'priority' => $automation->priority,
                'note' => '🤖 '.$automation->name."\n".$note,
                'due_at' => $this->businessHours->addWorkingDays((int) $automation->due_in_days),
                'status' => FollowUp::STATUS_OPEN,
                'last_activity_at' => now(),
            ]);

            $this->followers->follow($assignee, $context['entity_type'], $context['entity_id'], true);

            $this->notifier->notify($assignee, $urgent ? Notifier::FOLLOW_UP_URGENT : Notifier::AUTOMATION, $title, $note,
                route('admin.teamwork.follow_ups.show', $followUp->id, false), $urgent);

            return true;
        } catch (\Throwable $e) {
            Log::error('Teamwork automation '.$automation->id.' failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Who gets it: the record's owner, the agency owner(s), or a named user.
     */
    protected function recipients(Automation $automation, array $context): array
    {
        return match ($automation->assign_to) {
            'user' => array_filter([(int) $automation->assign_user_id]),
            'master' => $this->masters($automation->agency_id),
            default => array_filter([(int) $context['owner_id']]) ?: $this->masters($automation->agency_id),
        };
    }

    protected function masters(?int $agencyId): array
    {
        return User::with('role')
            ->when($agencyId, fn ($query) => $query->where('agency_id', $agencyId))
            ->where('status', 1)
            ->get()
            ->filter(fn ($user) => $this->teamScope->isMasterAgent($user))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function active(string $trigger, ?int $agencyId = null)
    {
        return Automation::where('trigger', $trigger)
            ->where('is_active', true)
            ->when($agencyId, fn ($query) => $query->where(fn ($q) => $q->whereNull('agency_id')->orWhere('agency_id', $agencyId)))
            ->get();
    }

    protected function leadContext($lead): array
    {
        $client = $lead->person_id ? DB::table('persons')->where('id', $lead->person_id)->value('name') : null;
        $stage = DB::table('lead_pipeline_stages')->where('id', $lead->lead_pipeline_stage_id)->value('name');

        return [
            'entity_type' => 'lead',
            'entity_id' => (int) $lead->id,
            'title' => (string) $lead->title,
            'url' => route('admin.leads.view', $lead->id, false),
            'owner_id' => $lead->user_id,
            'vars' => [
                'lead' => (string) $lead->title,
                'client' => $client ?: (string) $lead->title,
                'stage' => (string) $stage,
                'date' => now()->format('d/m/Y'),
                'idle' => '',
            ],
        ];
    }

    protected function personContext(int $personId, $ownerId): ?array
    {
        $name = DB::table('persons')->where('id', $personId)->value('name');

        if (! $name) {
            return null;
        }

        return [
            'entity_type' => 'person',
            'entity_id' => $personId,
            'title' => (string) $name,
            'url' => route('admin.contacts.persons.view', $personId, false),
            'owner_id' => $ownerId,
            'vars' => ['lead' => (string) $name, 'client' => (string) $name, 'stage' => '', 'date' => now()->format('d/m/Y'), 'idle' => ''],
        ];
    }

    /**
     * Replace {lead}, {client}, {stage}, {date}, {idle}.
     */
    protected function fill(string $template, array $vars): string
    {
        return strtr($template, collect($vars)->mapWithKeys(fn ($value, $key) => ['{'.$key.'}' => $value])->all());
    }
}
