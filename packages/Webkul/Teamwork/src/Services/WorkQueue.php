<?php

namespace Webkul\Teamwork\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Teamwork\Models\FollowUp;
use Webkul\Teamwork\Models\OverdueRule;

/**
 * What needs attention: open cases ranked by business hours without a
 * touch, and open follow-ups, each labelled ok / warning / overdue.
 */
class WorkQueue
{
    const OK = 'ok';

    const WARNING = 'warning';

    const OVERDUE = 'overdue';

    protected ?Collection $rules = null;

    public function __construct(protected BusinessHours $businessHours) {}

    /**
     * Open leads (not won/lost) owned by the given users, most neglected first.
     *
     * A "touch" is any activity on the lead (call, note, email, meeting,
     * stage change log…), an edit of the lead, or a Chatwoot message.
     */
    public function openCases(array $userIds, int $limit = 200): Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        $lastActivity = DB::table('lead_activities')
            ->join('activities', 'activities.id', '=', 'lead_activities.activity_id')
            ->whereColumn('lead_activities.lead_id', 'leads.id')
            ->selectRaw('max(activities.created_at)');

        $rows = DB::table('leads')
            ->join('lead_pipeline_stages as stages', 'stages.id', '=', 'leads.lead_pipeline_stage_id')
            ->leftJoin('lead_pipelines as pipelines', 'pipelines.id', '=', 'leads.lead_pipeline_id')
            ->leftJoin('users', 'users.id', '=', 'leads.user_id')
            ->leftJoin('persons', 'persons.id', '=', 'leads.person_id')
            ->whereIn('leads.user_id', $userIds)
            ->whereNotIn('stages.code', ['won', 'lost'])
            ->select([
                'leads.id',
                'leads.title',
                'leads.user_id',
                'leads.lead_pipeline_id',
                'leads.lead_pipeline_stage_id',
                'leads.created_at',
                'leads.updated_at',
                'leads.chatwoot_last_message_at',
                'stages.name as stage_name',
                'pipelines.name as pipeline_name',
                'users.name as owner_name',
                'persons.name as person_name',
            ])
            ->selectSub($lastActivity, 'last_activity_at')
            ->limit(2000)
            ->get();

        $now = now();

        return $rows->map(function ($row) use ($now) {
            $lastTouch = collect([$row->created_at, $row->updated_at, $row->last_activity_at, $row->chatwoot_last_message_at])
                ->filter()
                ->map(fn ($value) => Carbon::parse($value))
                ->max();

            $hours = $this->businessHours->between($lastTouch, $now);

            [$warning, $overdue] = $this->thresholdsFor($row->lead_pipeline_id, $row->lead_pipeline_stage_id);

            $row->last_touch_at = $lastTouch;
            $row->idle_hours = $hours;
            $row->idle_label = $this->businessHours->format($hours);
            $row->state = $hours >= $overdue ? self::OVERDUE : ($hours >= $warning ? self::WARNING : self::OK);
            $row->url = route('admin.leads.view', $row->id);

            return $row;
        })
            ->sortByDesc('idle_hours')
            ->take($limit)
            ->values();
    }

    /**
     * Open follow-ups assigned to the given users, urgent and late first.
     */
    public function openFollowUps(array $assigneeIds, ?int $createdBy = null): Collection
    {
        if (empty($assigneeIds) && ! $createdBy) {
            return collect();
        }

        $followUps = FollowUp::with(['assignee:id,name', 'creator:id,name'])
            ->where('status', FollowUp::STATUS_OPEN)
            ->where(function ($query) use ($assigneeIds, $createdBy) {
                $query->whereIn('assigned_to', $assigneeIds ?: [0]);

                if ($createdBy) {
                    $query->orWhere('created_by', $createdBy);
                }
            })
            ->get();

        $now = now();

        return $followUps->each(function (FollowUp $followUp) use ($now) {
            $followUp->state = $this->followUpState($followUp, $now);
            $followUp->age_label = $this->businessHours->format($this->businessHours->between($followUp->created_at, $now));
        })->sortBy(fn (FollowUp $followUp) => [
            $followUp->isUrgent() ? 0 : 1,
            ['overdue' => 0, 'warning' => 1, 'ok' => 2][$followUp->state],
            $followUp->due_at?->timestamp ?? PHP_INT_MAX,
        ])->values();
    }

    /**
     * Pending activities flagged urgent (the activity-level urgent flag from Leads),
     * with the lead they belong to.
     */
    public function urgentActivities(array $userIds): Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        return DB::table('activities')
            ->leftJoin('lead_activities', 'lead_activities.activity_id', '=', 'activities.id')
            ->leftJoin('leads', 'leads.id', '=', 'lead_activities.lead_id')
            ->leftJoin('users', 'users.id', '=', 'activities.user_id')
            ->whereIn('activities.user_id', $userIds)
            ->where('activities.is_done', 0)
            ->where('activities.priority', 'urgent')
            ->orderByRaw('coalesce(activities.schedule_from, activities.created_at)')
            ->limit(100)
            ->get([
                'activities.id',
                'activities.title',
                'activities.type',
                'activities.schedule_from',
                'activities.user_id',
                'users.name as owner_name',
                'leads.id as lead_id',
                'leads.title as lead_title',
            ])
            ->each(function ($activity) {
                $activity->url = $activity->lead_id ? route('admin.leads.view', $activity->lead_id) : route('admin.activities.index');
                $activity->state = $activity->schedule_from && Carbon::parse($activity->schedule_from)->isPast() ? self::OVERDUE : self::WARNING;
            });
    }

    /**
     * Leads escalated to the master agent (SLA escalation from Leads › Team Radar).
     */
    public function escalatedLeads(array $userIds): Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        return DB::table('leads')
            ->leftJoin('users', 'users.id', '=', 'leads.user_id')
            ->leftJoin('persons', 'persons.id', '=', 'leads.person_id')
            ->where('leads.sla_status', 'escalated')
            ->where(fn ($query) => $query->whereIn('leads.user_id', $userIds)->orWhereNull('leads.user_id'))
            ->orderBy('leads.escalated_at')
            ->limit(100)
            ->get([
                'leads.id',
                'leads.title',
                'leads.user_id',
                'leads.escalated_at',
                'leads.escalation_reason',
                'users.name as owner_name',
                'persons.name as person_name',
            ])
            ->each(function ($lead) {
                $lead->url = route('admin.leads.view', $lead->id);
                $lead->since_label = $lead->escalated_at
                    ? $this->businessHours->format($this->businessHours->between(Carbon::parse($lead->escalated_at), now()))
                    : '—';
            });
    }

    /**
     * One line per team member: open cases, warning, overdue, open follow-ups.
     */
    public function teamSummary(array $userIds): Collection
    {
        $cases = $this->openCases($userIds, PHP_INT_MAX)->groupBy('user_id');
        $followUps = $this->openFollowUps($userIds)->groupBy('assigned_to');

        return DB::table('users')
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($user) use ($cases, $followUps) {
                $mine = $cases->get($user->id, collect());
                $tasks = $followUps->get($user->id, collect());

                $user->open_cases = $mine->count();
                $user->warning = $mine->where('state', self::WARNING)->count();
                $user->overdue = $mine->where('state', self::OVERDUE)->count();
                $user->follow_ups = $tasks->count();
                $user->follow_ups_late = $tasks->where('state', self::OVERDUE)->count();
                $user->urgent = $tasks->filter->isUrgent()->count();
                $user->oldest_label = $mine->first()?->idle_label;

                return $user;
            })
            ->sortByDesc(fn ($user) => [$user->overdue + $user->follow_ups_late, $user->warning])
            ->values();
    }

    /**
     * ok / warning / overdue for a follow-up: by its due date if it has one,
     * otherwise by business hours open against the configured limit.
     */
    public function followUpState(FollowUp $followUp, ?Carbon $now = null): string
    {
        $now ??= now();

        if ($followUp->due_at) {
            if ($followUp->due_at->lt($now)) {
                return self::OVERDUE;
            }

            return $followUp->due_at->lt($now->copy()->addDay()) ? self::WARNING : self::OK;
        }

        $limit = $followUp->isUrgent()
            ? $this->configHours('urgent_overdue_hours', 4)
            : $this->configHours('follow_up_overdue_hours', 16);

        $hours = $this->businessHours->between($followUp->created_at, $now);

        return $hours >= $limit ? self::OVERDUE : ($hours >= $limit / 2 ? self::WARNING : self::OK);
    }

    /**
     * [warning, overdue] business hours for a case: stage rule, then pipeline rule, then defaults.
     */
    public function thresholdsFor(?int $pipelineId, ?int $stageId): array
    {
        $this->rules ??= OverdueRule::where('is_active', true)
            ->when(auth()->guard('user')->user()?->agency_id, fn ($query, $agencyId) => $query->where(fn ($q) => $q->whereNull('agency_id')->orWhere('agency_id', $agencyId)))
            ->get();

        $rule = ($stageId ? $this->rules->firstWhere('lead_pipeline_stage_id', $stageId) : null)
            ?? $this->rules->first(fn ($rule) => $rule->lead_pipeline_id === $pipelineId && ! $rule->lead_pipeline_stage_id)
            ?? $this->rules->first(fn ($rule) => ! $rule->lead_pipeline_id && ! $rule->lead_pipeline_stage_id);

        if ($rule) {
            return [(float) $rule->warning_hours, (float) max($rule->warning_hours, $rule->overdue_hours)];
        }

        $warning = $this->configHours('lead_warning_hours', 8);

        return [$warning, max($warning, $this->configHours('lead_overdue_hours', 16))];
    }

    public function businessHours(): BusinessHours
    {
        return $this->businessHours;
    }

    protected function configHours(string $field, float $default): float
    {
        $value = core()->getConfigData('general.teamwork.rules.'.$field);

        return is_numeric($value) && $value > 0 ? (float) $value : $default;
    }
}
