<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Webkul\Lead\Models\InsurancePolicy;

class PolicyRenewalService
{
    /**
     * Get Open Enrollment Period (OEP) renewal cohort and retention KPIs.
     */
    public function getOepRenewalCohort(int $planYear, array $filters = []): array
    {
        $priorYear = $planYear - 1;

        // Query policies from the prior year that qualify for renewal in the target plan year
        $query = InsurancePolicy::query()
            ->with(['priorPolicy', 'renewedPolicies', 'lead', 'person', 'user'])
            ->where(function ($q) use ($priorYear, $planYear) {
                $q->where('plan_year', $priorYear)
                    ->orWhere(function ($sub) use ($priorYear) {
                        $sub->whereNull('plan_year')
                            ->whereYear('effective_date', $priorYear);
                    })
                    ->orWhereYear('renewal_date', $priorYear)
                    ->orWhereYear('renewal_date', $planYear);
            });

        if (! empty($filters['carrier'])) {
            $query->where('carrier_name', $filters['carrier']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $allCohort = $query->orderBy('carrier_name')->orderBy('id', 'desc')->get();

        $renewedSameCarrier = 0;
        $renewedCrossCarrier = 0;
        $pendingCount = 0;
        $cancelledCount = 0;

        $retainedGrossVolume = 0.0;
        $retainedNetVolume = 0.0;
        $premiumDiffTotal = 0.0;

        $items = $allCohort->map(function (InsurancePolicy $policy) use (
            &$renewedSameCarrier,
            &$renewedCrossCarrier,
            &$pendingCount,
            &$cancelledCount,
            &$retainedGrossVolume,
            &$retainedNetVolume,
            &$premiumDiffTotal
        ) {
            $renewedChild = $policy->renewedPolicies->first();
            $status = 'pending';
            $comparison = null;

            if ($renewedChild) {
                $comparison = $renewedChild->getYearOverYearComparison($policy);
                $isCarrierMatch = strcasecmp((string) $renewedChild->carrier_name, (string) $policy->carrier_name) === 0;

                if ($isCarrierMatch) {
                    $status = 'renewed_same_carrier';
                    $renewedSameCarrier++;
                } else {
                    $status = 'renewed_cross_carrier';
                    $renewedCrossCarrier++;
                }

                $retainedGrossVolume += (float) $renewedChild->gross_premium;
                $retainedNetVolume += (float) $renewedChild->net_premium;
                $premiumDiffTotal += ($comparison['variance']['net_premium_diff'] ?? 0);
            } elseif ($policy->status === 'renewed') {
                $status = 'renewed_same_carrier';
                $renewedSameCarrier++;
                $retainedGrossVolume += (float) $policy->gross_premium;
                $retainedNetVolume += (float) $policy->net_premium;
            } elseif ($policy->status === 'cancelled') {
                $status = 'cancelled';
                $cancelledCount++;
            } else {
                $status = 'pending';
                $pendingCount++;
            }

            return [
                'id' => $policy->id,
                'policy_number' => $policy->policy_number,
                'client_name' => $policy->person?->name ?: ($policy->lead?->title ?: 'Sin Nombre'),
                'client_phone' => $policy->person?->contact_numbers[0]['value'] ?? null,
                'client_email' => $policy->person?->emails[0]['value'] ?? null,
                'agent_name' => $policy->user?->name ?: 'N/A',
                'carrier_name' => $policy->carrier_name,
                'plan_name' => $policy->plan_name,
                'metal_tier' => $policy->metal_tier,
                'plan_year' => $policy->plan_year ?: ($policy->effective_date ? Carbon::parse($policy->effective_date)->year : null),
                'gross_premium' => (float) $policy->gross_premium,
                'aptc_subsidy' => (float) $policy->aptc_subsidy,
                'net_premium' => (float) $policy->net_premium,
                'deductible' => (float) ($policy->deductible ?? 0),
                'max_out_of_pocket' => (float) ($policy->max_out_of_pocket ?? 0),
                'effective_date' => $policy->effective_date?->toDateString(),
                'renewal_date' => $policy->renewal_date?->toDateString(),
                'policy_status' => $policy->status,
                'oep_renewal_status' => $status,
                'renewed_policy_id' => $renewedChild?->id,
                'renewed_policy_number' => $renewedChild?->policy_number,
                'comparison' => $comparison,
            ];
        });

        $totalCount = $allCohort->count();
        $totalRenewed = $renewedSameCarrier + $renewedCrossCarrier;
        $retentionRate = $totalCount > 0 ? round(($totalRenewed / $totalCount) * 100, 1) : 0.0;
        $avgPremiumDelta = $totalRenewed > 0 ? round($premiumDiffTotal / $totalRenewed, 2) : 0.0;

        return [
            'plan_year' => $planYear,
            'prior_year' => $priorYear,
            'kpis' => [
                'total_cohort' => $totalCount,
                'renewed_total' => $totalRenewed,
                'renewed_same_carrier' => $renewedSameCarrier,
                'renewed_cross_carrier' => $renewedCrossCarrier,
                'pending_review' => $pendingCount,
                'cancelled' => $cancelledCount,
                'retention_rate' => $retentionRate,
                'retained_gross_volume' => round($retainedGrossVolume, 2),
                'retained_net_volume' => round($retainedNetVolume, 2),
                'avg_net_premium_delta' => $avgPremiumDelta,
            ],
            'policies' => $items->values()->toArray(),
        ];
    }

    /**
     * Process Year-Over-Year OEP Renewal and create new linked policy.
     */
    public function processRenewal(InsurancePolicy $priorPolicy, array $data, ?int $userId = null): InsurancePolicy
    {
        $planYear = (int) ($data['plan_year'] ?? ($priorPolicy->plan_year ? $priorPolicy->plan_year + 1 : now()->year + 1));
        $carrierName = trim($data['carrier_name'] ?? $priorPolicy->carrier_name);
        $planName = trim($data['plan_name'] ?? $priorPolicy->plan_name);

        // Determine renewal type classification
        $isSameCarrier = strcasecmp((string) $carrierName, (string) $priorPolicy->carrier_name) === 0;
        $isSamePlan = strcasecmp((string) $planName, (string) $priorPolicy->plan_name) === 0;

        if ($isSameCarrier && $isSamePlan) {
            $renewalType = 'same_plan';
        } elseif ($isSameCarrier) {
            $renewalType = 'same_carrier_switch';
        } else {
            $renewalType = 'cross_carrier_switch';
        }

        $grossPremium = (float) ($data['gross_premium'] ?? $priorPolicy->gross_premium);
        $aptcSubsidy = (float) ($data['aptc_subsidy'] ?? $priorPolicy->aptc_subsidy);
        $netPremium = isset($data['net_premium']) ? (float) $data['net_premium'] : max(0, $grossPremium - $aptcSubsidy);
        $deductible = (float) ($data['deductible'] ?? $priorPolicy->deductible ?? 0);
        $moop = (float) ($data['max_out_of_pocket'] ?? $priorPolicy->max_out_of_pocket ?? 0);

        $effectiveDate = $data['effective_date'] ?? Carbon::create($planYear, 1, 1)->toDateString();
        $renewalDate = $data['renewal_date'] ?? Carbon::create($planYear, 12, 31)->toDateString();

        // Effectuation logic based on zero premium vs positive premium
        $isZeroPremium = $netPremium <= 0;
        $status = $isZeroPremium ? 'active' : 'binder_pending';
        $binderStatus = $isZeroPremium ? 'waived_zero_premium' : 'pending';
        $effectuationDate = $isZeroPremium ? $effectiveDate : null;
        $effectuationSource = $isZeroPremium ? 'zero_dollar_subsidy' : null;

        $newPolicyNumber = ! empty($data['policy_number'])
            ? trim($data['policy_number'])
            : ($priorPolicy->policy_number.'-'.$planYear);

        // Create the new renewed policy
        $newPolicy = InsurancePolicy::create([
            'policy_number' => $newPolicyNumber,
            'lead_id' => $priorPolicy->lead_id,
            'person_id' => $priorPolicy->person_id,
            'user_id' => $userId ?: $priorPolicy->user_id,
            'quote_id' => $priorPolicy->quote_id,
            'carrier_name' => $carrierName,
            'plan_name' => $planName,
            'metal_tier' => $data['metal_tier'] ?? $priorPolicy->metal_tier,
            'network_type' => $data['network_type'] ?? $priorPolicy->network_type,
            'market_type' => $priorPolicy->market_type ?: 'aca_individual',
            'plan_year' => $planYear,
            'gross_premium' => $grossPremium,
            'aptc_subsidy' => $aptcSubsidy,
            'net_premium' => $netPremium,
            'deductible' => $deductible,
            'max_out_of_pocket' => $moop,
            'effective_date' => $effectiveDate,
            'renewal_date' => $renewalDate,
            'paid_to_date' => $isZeroPremium ? Carbon::parse($effectiveDate)->endOfMonth()->toDateString() : null,
            'status' => $status,
            'binder_payment_status' => $binderStatus,
            'binder_amount' => $isZeroPremium ? 0.0 : $netPremium,
            'effectuation_date' => $effectuationDate,
            'effectuation_source' => $effectuationSource,
            'effectuation_verified_by' => $isZeroPremium ? ($userId ?: $priorPolicy->user_id) : null,
            'prior_policy_id' => $priorPolicy->id,
            'renewal_type' => $renewalType,
            'renewal_cohort_year' => $planYear,
            'renewal_notes' => $data['notes'] ?? "Renovación OEP {$planYear} procesada desde póliza previa #{$priorPolicy->policy_number}.",
            'members_count' => $priorPolicy->members_count ?: 1,
            'notes' => $data['notes'] ?? null,
        ]);

        // Record status history for new policy
        $newPolicy->recordCoverageTransition(
            toStatus: $status,
            binderStatus: $binderStatus,
            reason: "Póliza renovada año {$planYear} vinculada a póliza previa #{$priorPolicy->policy_number} ({$renewalType}).",
            source: 'agent_manual',
            userId: $userId
        );

        // Update prior policy status to 'renewed'
        $priorPolicy->update([
            'status' => 'renewed',
            'notes' => trim(($priorPolicy->notes ?: '')."\n[".now()->toDateString()."] Renovada para {$planYear} con nueva póliza #{$newPolicy->policy_number}."),
        ]);

        $priorPolicy->recordCoverageTransition(
            toStatus: 'renewed',
            binderStatus: $priorPolicy->binder_payment_status,
            reason: "Renovación OEP completada. Sucedida por póliza #{$newPolicy->policy_number}.",
            source: 'agent_manual',
            userId: $userId
        );

        return $newPolicy;
    }

    /**
     * Preview side-by-side comparison before saving renewal.
     */
    public function previewComparison(InsurancePolicy $priorPolicy, array $proposedData): array
    {
        $planYear = (int) ($proposedData['plan_year'] ?? ($priorPolicy->plan_year ? $priorPolicy->plan_year + 1 : now()->year + 1));
        $grossPremium = (float) ($proposedData['gross_premium'] ?? $priorPolicy->gross_premium);
        $aptcSubsidy = (float) ($proposedData['aptc_subsidy'] ?? $priorPolicy->aptc_subsidy);
        $netPremium = isset($proposedData['net_premium']) ? (float) $proposedData['net_premium'] : max(0, $grossPremium - $aptcSubsidy);
        $deductible = (float) ($proposedData['deductible'] ?? $priorPolicy->deductible ?? 0);
        $moop = (float) ($proposedData['max_out_of_pocket'] ?? $priorPolicy->max_out_of_pocket ?? 0);

        $dummyPolicy = new InsurancePolicy([
            'id' => 0,
            'policy_number' => $proposedData['policy_number'] ?? ($priorPolicy->policy_number.'-'.$planYear),
            'carrier_name' => $proposedData['carrier_name'] ?? $priorPolicy->carrier_name,
            'plan_name' => $proposedData['plan_name'] ?? $priorPolicy->plan_name,
            'metal_tier' => $proposedData['metal_tier'] ?? $priorPolicy->metal_tier,
            'plan_year' => $planYear,
            'gross_premium' => $grossPremium,
            'aptc_subsidy' => $aptcSubsidy,
            'net_premium' => $netPremium,
            'deductible' => $deductible,
            'max_out_of_pocket' => $moop,
            'status' => $netPremium <= 0 ? 'active' : 'binder_pending',
        ]);

        return $dummyPolicy->getYearOverYearComparison($priorPolicy);
    }
}
