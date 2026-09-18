<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\SlaRuleRepository;
use Webkul\User\Repositories\UserRepository;

class SlaEscalationService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected LeadRepository $leadRepository,
        protected SlaRuleRepository $slaRuleRepository,
        protected ActivityRepository $activityRepository,
        protected UserRepository $userRepository
    ) {}

    /**
     * Check overdue leads and escalate those exceeding escalation_hours.
     *
     * @return int Number of leads escalated
     */
    public function checkAndEscalate(): int
    {
        $overdueLeads = $this->leadRepository
            ->where('sla_status', 'overdue')
            ->whereNull('escalated_at')
            ->whereNotNull('assigned_at')
            ->get();

        $escalatedCount = 0;
        $now = Carbon::now();

        foreach ($overdueLeads as $lead) {
            $rule = $this->slaRuleRepository->getRuleForLead(
                $lead->lead_pipeline_id,
                $lead->lead_type_id
            );

            $firstContactHours = (int) ($rule->first_contact_hours ?? 2);
            $escalationHours   = (int) ($rule->escalation_hours ?? 4);

            // Escalation threshold is assigned_at + first_contact_hours + escalation_hours
            $escalationDeadline = Carbon::parse($lead->assigned_at)
                ->addHours($firstContactHours + $escalationHours);

            if ($now->greaterThanOrEqualTo($escalationDeadline)) {
                $this->escalateLead(
                    $lead,
                    "Excedió el tiempo límite de SLA ({$escalationHours}h tras vencer)"
                );
                $escalatedCount++;
            }
        }

        return $escalatedCount;
    }

    /**
     * Escalate a specific lead.
     *
     * @param  \Webkul\Lead\Models\Lead|int  $lead
     * @param  string  $reason
     * @param  int|null  $requestedBy
     * @return bool
     */
    public function escalateLead($lead, string $reason, ?int $requestedBy = null): bool
    {
        if (is_int($lead)) {
            $lead = $this->leadRepository->find($lead);
        }

        if (! $lead) {
            return false;
        }

        $now = Carbon::now();

        $this->leadRepository->update([
            'sla_status'        => 'escalated',
            'escalated_at'      => $now,
            'escalation_reason' => $reason,
        ], $lead->id);

        // Also update activities
        DB::table('activities')
            ->join('lead_activities', 'activities.id', '=', 'lead_activities.activity_id')
            ->where('lead_activities.lead_id', $lead->id)
            ->where('activities.is_done', false)
            ->update([
                'activities.priority'            => 'urgent',
                'activities.sla_activity_status' => 'overdue',
            ]);

        // Find Master Agent or Admin user to assign the escalation alert activity
        $adminUser = $this->userRepository->findWhere(['status' => 1])->first();
        $assigneeId = $adminUser ? $adminUser->id : $lead->user_id;

        $activity = $this->activityRepository->create([
            'title'               => '🚨 ESCALACIÓN SLA: '.$lead->title,
            'type'                => 'note',
            'comment'             => 'Caso escalado a Torre de Control / Agente Maestro. Motivo: '.$reason,
            'schedule_from'       => $now,
            'schedule_to'         => $now->copy()->addHours(2),
            'is_done'             => false,
            'user_id'             => $assigneeId,
            'priority'            => 'urgent',
            'sla_activity_status' => 'overdue',
        ]);

        $activity->leads()->attach($lead->id);

        return true;
    }
}
