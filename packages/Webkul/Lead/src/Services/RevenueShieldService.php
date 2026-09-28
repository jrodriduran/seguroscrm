<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Webkul\Lead\Models\InsuranceCommission;
use Webkul\Lead\Models\InsurancePolicy;

class RevenueShieldService
{
    /**
     * Standard estimated monthly commission (PMPM) per active member when carrier rate is unconfigured.
     */
    const DEFAULT_MONTHLY_PMPM = 28.00;

    /**
     * Run the Revenue Shield continuous audit algorithm across all active policies.
     */
    public function runAudit(): array
    {
        $cutoffDate = Carbon::today()->subDays(45);
        $recentCommissionThreshold = Carbon::today()->subDays(60);

        $activePolicies = InsurancePolicy::with(['lead.person'])
            ->where('status', 'active')
            ->where(function ($q) use ($cutoffDate) {
                $q->whereNull('effective_date')
                    ->orWhereDate('effective_date', '<=', $cutoffDate);
            })
            ->get();

        $stats = [
            'total_audited' => $activePolicies->count(),
            'flagged_missing' => 0,
            'cleared_resolved' => 0,
            'total_uncollected_amount' => 0.0,
            'carrier_breakdown' => [],
        ];

        foreach ($activePolicies as $policy) {
            // Check if any commission was reconciled for this policy within the last 60 days
            $hasRecentCommission = InsuranceCommission::where('policy_number', $policy->policy_number)
                ->where('status', 'paid')
                ->whereDate('created_at', '>=', $recentCommissionThreshold)
                ->exists();

            if (! $hasRecentCommission) {
                $effectiveDate = $policy->effective_date ? Carbon::parse($policy->effective_date) : Carbon::today()->subDays(45);
                $daysActive = (int) $effectiveDate->diffInDays(Carbon::today());
                $unpaidMonths = max(1, (int) ceil($daysActive / 30));

                $monthlyRate = (float) ($policy->carrierRate?->commission_amount ?? self::DEFAULT_MONTHLY_PMPM);
                $estimatedUnpaid = round($unpaidMonths * $monthlyRate * max(1, (int) $policy->members_count), 2);

                $policy->update([
                    'missing_commission_flag' => true,
                    'missing_commission_detected_at' => $policy->missing_commission_detected_at ?: Carbon::now(),
                    'missing_commission_amount' => $estimatedUnpaid,
                    'missing_commission_days' => $daysActive,
                    'missing_commission_notes' => sprintf(
                        'Alerta Revenue Shield: Póliza activa sin comisión reportada por el carrier (%s) en los últimos %d días.',
                        $policy->carrier_name,
                        $daysActive
                    ),
                ]);

                $stats['flagged_missing']++;
                $stats['total_uncollected_amount'] += $estimatedUnpaid;

                $carrier = $policy->carrier_name ?: 'Desconocido';
                if (! isset($stats['carrier_breakdown'][$carrier])) {
                    $stats['carrier_breakdown'][$carrier] = ['count' => 0, 'amount' => 0.0];
                }
                $stats['carrier_breakdown'][$carrier]['count']++;
                $stats['carrier_breakdown'][$carrier]['amount'] += $estimatedUnpaid;
            } else {
                if ($policy->missing_commission_flag) {
                    $policy->update([
                        'missing_commission_flag' => false,
                        'missing_commission_notes' => ($policy->missing_commission_notes ? $policy->missing_commission_notes."\n" : '').
                            '['.Carbon::now()->toDateTimeString().'] Comisión recibida y conciliada. Alerta resuelta automáticamente.',
                    ]);
                    $stats['cleared_resolved']++;
                }
            }
        }

        $stats['total_uncollected_amount'] = round($stats['total_uncollected_amount'], 2);

        return $stats;
    }

    /**
     * Get summary metrics and flagged policies for the Revenue Shield dashboard.
     */
    public function getSummary(?int $userId = null): array
    {
        $query = InsurancePolicy::with(['lead.person'])
            ->where('missing_commission_flag', true);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $flaggedPolicies = $query->orderBy('missing_commission_amount', 'desc')->get();

        $totalAmount = (float) $flaggedPolicies->sum('missing_commission_amount');
        $carrierBreakdown = [];

        foreach ($flaggedPolicies as $policy) {
            $carrier = $policy->carrier_name ?: 'Desconocido';
            if (! isset($carrierBreakdown[$carrier])) {
                $carrierBreakdown[$carrier] = ['count' => 0, 'amount' => 0.0];
            }
            $carrierBreakdown[$carrier]['count']++;
            $carrierBreakdown[$carrier]['amount'] += (float) $policy->missing_commission_amount;
        }

        return [
            'metrics' => [
                'total_missing_policies' => $flaggedPolicies->count(),
                'total_uncollected_revenue' => round($totalAmount, 2),
                'carriers_affected_count' => count($carrierBreakdown),
            ],
            'carrier_breakdown' => $carrierBreakdown,
            'policies' => $flaggedPolicies->map(function ($policy) {
                return [
                    'id' => $policy->id,
                    'lead_id' => $policy->lead_id,
                    'policy_number' => $policy->policy_number,
                    'carrier_name' => $policy->carrier_name,
                    'plan_name' => $policy->plan_name,
                    'client_name' => $policy->insured_name ?: ($policy->lead?->person?->name ?? 'Asegurado'),
                    'effective_date' => $policy->effective_date?->format('Y-m-d'),
                    'missing_days' => $policy->missing_commission_days,
                    'missing_amount' => (float) $policy->missing_commission_amount,
                    'detected_at' => $policy->missing_commission_detected_at?->format('Y-m-d H:i'),
                    'notes' => $policy->missing_commission_notes,
                ];
            })->toArray(),
        ];
    }

    /**
     * Resolve a missing commission flag manually with a dispute or claim reference.
     */
    public function resolveMissing(int $policyId, string $resolutionNote, ?string $claimTicket = null): bool
    {
        $policy = InsurancePolicy::findOrFail($policyId);

        $appendNote = sprintf(
            '[%s] Reclamo de comisión resuelto: %s%s',
            Carbon::now()->toDateTimeString(),
            $resolutionNote,
            $claimTicket ? " (Ticket Carrier: #{$claimTicket})" : ''
        );

        return $policy->update([
            'missing_commission_flag' => false,
            'missing_commission_notes' => trim(($policy->missing_commission_notes ?: '')."\n".$appendNote),
        ]);
    }
}
