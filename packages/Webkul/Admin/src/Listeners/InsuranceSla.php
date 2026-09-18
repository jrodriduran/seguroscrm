<?php

namespace Webkul\Admin\Listeners;

use Carbon\Carbon;
use Webkul\Activity\Models\Activity;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\SlaRuleRepository;

class InsuranceSla
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected LeadRepository $leadRepository,
        protected ActivityRepository $activityRepository,
        protected SlaRuleRepository $slaRuleRepository
    ) {}

    /**
     * Handle lead.create.after — stamp assigned_at and auto-create SLA activity.
     *
     * @param  Lead  $lead
     */
    public function onLeadCreate($lead): void
    {
        if (! $lead->user_id) {
            return;
        }

        $this->stampAssignedAt($lead);
        $this->createFirstContactActivity($lead);
    }

    /**
     * Handle lead.update.after — reset SLA clock if the assigned agent changed.
     *
     * @param  Lead  $lead
     */
    public function onLeadUpdate($lead): void
    {
        if (! $lead->user_id) {
            return;
        }

        // Only reset when the agent changed
        $original = $lead->getOriginal('user_id');

        if ($original && (int) $original !== (int) $lead->user_id) {
            $this->stampAssignedAt($lead);
            $this->createFirstContactActivity($lead);
        }
    }

    /**
     * Handle activity.create.after / activity.update.after
     * — resolve the SLA on the parent lead when a call/note is registered.
     *
     * @param  Activity  $activity
     */
    public function onActivitySaved($activity): void
    {
        if (! $activity->is_done) {
            return;
        }

        // Touch all leads linked to this activity.
        $activity->load('leads');

        foreach ($activity->leads as $lead) {
            if (in_array($lead->sla_status, ['pending', 'overdue', 'escalated'], true)) {
                $this->leadRepository->update(
                    ['sla_status' => 'active'],
                    $lead->id
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Stamp assigned_at, configured sla_hours, and reset sla_status to pending.
     *
     * @param  Lead  $lead
     */
    private function stampAssignedAt($lead): void
    {
        $rule = $this->slaRuleRepository->getRuleForLead(
            $lead->lead_pipeline_id,
            $lead->lead_type_id
        );

        $slaHours = (int) ($rule->first_contact_hours ?? 2);

        $this->leadRepository->update(
            [
                'assigned_at' => Carbon::now(),
                'sla_status' => 'pending',
                'sla_hours' => $slaHours,
            ],
            $lead->id
        );
    }

    /**
     * Auto-create a "Primer Contacto" activity for the assigned agent based on configurable SLA.
     *
     * @param  Lead  $lead
     */
    private function createFirstContactActivity($lead): void
    {
        $now = Carbon::now();

        $rule = $this->slaRuleRepository->getRuleForLead(
            $lead->lead_pipeline_id,
            $lead->lead_type_id
        );

        $slaHours = (int) ($rule->first_contact_hours ?? $lead->sla_hours ?? 2);

        $activity = $this->activityRepository->create([
            'title' => trans('admin::insurance.team_radar.first_contact_title'),
            'type' => 'call',
            'comment' => trans('admin::insurance.team_radar.first_contact_comment', [
                'lead' => $lead->title,
                'hours' => $slaHours,
            ]),
            'schedule_from' => $now,
            'schedule_to' => $now->copy()->addHours($slaHours),
            'is_done' => false,
            'user_id' => $lead->user_id,
            'priority' => 'urgent',
            'sla_activity_status' => 'pending',
        ]);

        // Link activity → lead
        $activity->leads()->attach($lead->id);
    }
}
