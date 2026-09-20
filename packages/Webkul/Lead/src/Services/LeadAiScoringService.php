<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Webkul\Lead\Models\Lead;

class LeadAiScoringService
{
    /**
     * Compute multidimensional AI Lead Score (0 - 100) and Next Best Actions.
     */
    public function evaluateLead(Lead $lead): array
    {
        $lead->loadMissing(['person', 'consent', 'dmiDocuments', 'sepQualification', 'rxMedications', 'doctorNetworks', 'householdMembers']);

        $scoreBreakdown = [
            'sep_oep_urgency' => 0,
            'medicare_turning_65' => 0,
            'subsidy_potential' => 0,
            'compliance_readiness' => 0,
            'retention_dmi_urgency' => 0,
        ];

        $nextBestActions = [];

        // 1. Evaluate SEP / OEP Urgency Factor (Max 30 pts)
        $sep = $lead->sepQualification;
        $now = Carbon::now();

        // Check if national OEP is active (Nov 1 to Jan 15)
        $month = (int) $now->format('n');
        $isOepActive = ($month === 11 || $month === 12 || ($month === 1 && (int) $now->format('j') <= 15));

        if ($sep && ($sep->is_eligible || $sep->status === 'qualified')) {
            $daysRemaining = $sep->days_remaining;
            if ($daysRemaining > 0 && $daysRemaining <= 7) {
                $scoreBreakdown['sep_oep_urgency'] = 30;
                $nextBestActions[] = [
                    'priority' => 'urgent',
                    'icon' => '🚨',
                    'title' => trans('admin::insurance.ai_insights.sep_urgent_title', ['days' => $daysRemaining]),
                    'description' => trans('admin::insurance.ai_insights.sep_urgent_desc'),
                    'action_label' => trans('admin::insurance.ai_insights.sep_urgent_action'),
                    'tab' => 'enrollment_period',
                ];
            } elseif ($daysRemaining > 7 && $daysRemaining <= 30) {
                $scoreBreakdown['sep_oep_urgency'] = 20;
                $nextBestActions[] = [
                    'priority' => 'high',
                    'icon' => '⏳',
                    'title' => trans('admin::insurance.ai_insights.sep_active_title', ['days' => $daysRemaining]),
                    'description' => trans('admin::insurance.ai_insights.sep_active_desc'),
                    'action_label' => trans('admin::insurance.ai_insights.sep_active_action'),
                    'tab' => 'enrollment_period',
                ];
            }
        } elseif ($isOepActive) {
            $scoreBreakdown['sep_oep_urgency'] = 20;
            $nextBestActions[] = [
                'priority' => 'high',
                'icon' => '🗓️',
                'title' => trans('admin::insurance.ai_insights.oep_active_title'),
                'description' => trans('admin::insurance.ai_insights.oep_active_desc'),
                'action_label' => trans('admin::insurance.ai_insights.oep_active_action'),
                'tab' => 'quotes',
            ];
        } else {
            // Outside OEP and no SEP qualified
            $nextBestActions[] = [
                'priority' => 'medium',
                'icon' => '🔍',
                'title' => trans('admin::insurance.ai_insights.sep_qualify_title'),
                'description' => trans('admin::insurance.ai_insights.sep_qualify_desc'),
                'action_label' => trans('admin::insurance.ai_insights.sep_qualify_action'),
                'tab' => 'enrollment_period',
            ];
        }

        // 2. Evaluate Medicare Aging-In (Turning 65 within 6 months) (Max 25 pts)
        $isTurning65 = false;
        $birthDate = null;

        // Check lead's person or household members
        if ($lead->person && ! empty($lead->person->date_of_birth)) {
            $birthDate = Carbon::parse($lead->person->date_of_birth);
        } elseif ($lead->householdMembers && $lead->householdMembers->isNotEmpty()) {
            $applicant = $lead->householdMembers->firstWhere('relationship', 'primary_subscriber') ?: $lead->householdMembers->first();
            if ($applicant && ! empty($applicant->date_of_birth)) {
                $birthDate = Carbon::parse($applicant->date_of_birth);
            }
        }

        if ($birthDate) {
            $ageInMonths = $birthDate->diffInMonths($now);
            $ageInYears = $birthDate->age;

            // Age between 64 years 6 months and 65 years 3 months (Medicare IEP Window)
            if ($ageInMonths >= (64 * 12 + 6) && $ageInMonths <= (65 * 12 + 3)) {
                $isTurning65 = true;
                $scoreBreakdown['medicare_turning_65'] = 25;
                $nextBestActions[] = [
                    'priority' => 'high',
                    'icon' => '🎂',
                    'title' => trans('admin::insurance.ai_insights.medicare_title'),
                    'description' => trans('admin::insurance.ai_insights.medicare_desc'),
                    'action_label' => trans('admin::insurance.ai_insights.medicare_action'),
                    'tab' => 'medicare_soa',
                ];
            }
        }

        // 3. APTC Subsidy Potential (Silver CSR 94% / 87%) (Max 20 pts)
        $householdSize = $lead->householdMembers?->count() ?: 1;
        // Check if household has members or estimated subsidy potential
        if ($householdSize >= 2) {
            $scoreBreakdown['subsidy_potential'] = 20; // High subsidy probability for families
        } else {
            $scoreBreakdown['subsidy_potential'] = 15;
        }

        // 4. Compliance & Readiness (Max 15 pts)
        $hasConsent = ($lead->consent && $lead->consent->status === 'signed');
        $hasSoa = ($lead->medicareSoas && $lead->medicareSoas->where('status', 'signed')->isNotEmpty());

        if ($hasConsent || $hasSoa) {
            $scoreBreakdown['compliance_readiness'] += 10;
        } else {
            $nextBestActions[] = [
                'priority' => 'urgent',
                'icon' => '📋',
                'title' => trans('admin::insurance.ai_insights.consent_missing_title'),
                'description' => trans('admin::insurance.ai_insights.consent_missing_desc'),
                'action_label' => trans('admin::insurance.ai_insights.consent_missing_action'),
                'tab' => 'consent',
            ];
        }

        // Rx / Provider check
        $hasRx = ($lead->rxMedications && $lead->rxMedications->isNotEmpty());
        $hasDoctor = ($lead->doctorNetworks && $lead->doctorNetworks->isNotEmpty());

        if ($hasRx || $hasDoctor) {
            $scoreBreakdown['compliance_readiness'] += 5;
        } else {
            $nextBestActions[] = [
                'priority' => 'info',
                'icon' => '💊',
                'title' => trans('admin::insurance.ai_insights.rx_missing_title'),
                'description' => trans('admin::insurance.ai_insights.rx_missing_desc'),
                'action_label' => trans('admin::insurance.ai_insights.rx_missing_action'),
                'tab' => 'rx_network',
            ];
        }

        // 5. Retention / DMI Deadline Risk (Max 10 pts)
        $urgentDmi = $lead->dmiDocuments?->first(fn ($doc) => $doc->status === 'pending_upload' && $doc->urgency_level === 'critical');
        if ($urgentDmi) {
            $scoreBreakdown['retention_dmi_urgency'] = 10;
            $nextBestActions[] = [
                'priority' => 'urgent',
                'icon' => '⚠️',
                'title' => trans('admin::insurance.ai_insights.dmi_urgent_title', ['days' => $urgentDmi->days_remaining]),
                'description' => trans('admin::insurance.ai_insights.dmi_urgent_desc', ['title' => $urgentDmi->title]),
                'action_label' => trans('admin::insurance.ai_insights.dmi_urgent_action'),
                'tab' => 'dmi_documents',
            ];
        }

        // Total calculation
        $totalScore = min(100, array_sum($scoreBreakdown));

        $tier = match (true) {
            $totalScore >= 75 => ['label' => trans('admin::insurance.ai_insights.hot_lead'), 'badge' => trans('admin::insurance.ai_insights.tier_hot'), 'color' => 'rose'],
            $totalScore >= 50 => ['label' => trans('admin::insurance.ai_insights.warm_lead'), 'badge' => trans('admin::insurance.ai_insights.tier_warm'), 'color' => 'amber'],
            default => ['label' => trans('admin::insurance.ai_insights.nurture_lead'), 'badge' => trans('admin::insurance.ai_insights.tier_nurture'), 'color' => 'blue'],
        };

        // Sort next best actions by priority: urgent, high, medium, info
        $priorityOrder = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'info' => 4];
        usort($nextBestActions, fn ($a, $b) => ($priorityOrder[$a['priority']] ?? 5) <=> ($priorityOrder[$b['priority']] ?? 5));

        return [
            'lead_id' => $lead->id,
            'total_score' => $totalScore,
            'tier' => $tier,
            'breakdown' => $scoreBreakdown,
            'next_best_actions' => $nextBestActions,
            'evaluated_at' => now()->toIso8601String(),
        ];
    }
}
