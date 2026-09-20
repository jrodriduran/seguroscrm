<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Webkul\Lead\Models\Lead;

class ClientSnapshotService
{
    public function __construct(
        protected LeadAiScoringService $aiScoringService
    ) {}

    /**
     * Generate 360-degree executive snapshot for quick agent briefing.
     */
    public function generateSnapshot(Lead $lead): array
    {
        $lead->loadMissing([
            'person',
            'user',
            'householdMembers',
            'rxMedications',
            'doctorNetworks',
            'consent',
            'dmiDocuments',
            'sepQualification',
            'quotes',
        ]);

        $person = $lead->person;
        $household = $lead->householdMembers;
        $medications = $lead->rxMedications;
        $doctors = $lead->doctorNetworks;
        $primaryDoctor = $doctors->firstWhere('is_primary_physician', true) ?: $doctors->first();
        $consent = $lead->consent;
        $urgentDmi = $lead->dmiDocuments?->first(fn ($d) => $d->urgency_level === 'critical');

        $aiInsights = $this->aiScoringService->evaluateLead($lead);
        $nextAction = ! empty($aiInsights['next_best_actions']) ? $aiInsights['next_best_actions'][0]['title'] : trans('admin::insurance.ai_insights.tier_nurture');

        // Age calculation
        $ageText = trans('admin::insurance.ai_snapshot.age_unspecified');
        if ($person?->date_of_birth) {
            $ageText = trans('admin::insurance.ai_snapshot.age_years', ['age' => Carbon::parse($person->date_of_birth)->age]);
        }

        // Meds summary
        $medsSummary = $medications->isNotEmpty()
            ? $medications->pluck('medication_name')->implode(', ')
            : trans('admin::insurance.ai_snapshot.no_meds');

        $pcpSummary = $primaryDoctor
            ? "{$primaryDoctor->doctor_name} ({$primaryDoctor->specialty})"
            : trans('admin::insurance.ai_snapshot.pcp_pending');

        $complianceStatus = ($consent && $consent->status === 'signed')
            ? trans('admin::insurance.ai_snapshot.consent_signed')
            : trans('admin::insurance.ai_snapshot.consent_required');

        if ($urgentDmi) {
            $complianceStatus .= ' • ⚠️ '.trans('admin::insurance.ai_snapshot.dmi_critical_suffix', ['days' => $urgentDmi->days_remaining]);
        }

        // Narrative Brief
        $narrative = trans('admin::insurance.ai_snapshot.narrative_template', [
            'name' => $person?->name ?: $lead->title,
            'age' => $ageText,
            'location' => $person?->address ?: trans('admin::insurance.policies.address_not_specified', [], 'en') ?: 'Zipcode',
            'household' => $household->count() + 1,
            'pcp' => $pcpSummary,
            'meds' => $medsSummary,
            'compliance' => $complianceStatus,
            'score' => $aiInsights['total_score'],
            'tier' => $aiInsights['tier']['label'],
            'action' => $nextAction,
        ]);

        return [
            'lead_id' => $lead->id,
            'client_name' => $person?->name ?: $lead->title,
            'age_text' => $ageText,
            'household_size' => $household->count() + 1,
            'primary_doctor' => $pcpSummary,
            'medications_summary' => $medsSummary,
            'compliance_status' => $complianceStatus,
            'ai_score' => $aiInsights['total_score'],
            'ai_tier' => $aiInsights['tier'],
            'next_action' => $nextAction,
            'executive_narrative' => $narrative,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
