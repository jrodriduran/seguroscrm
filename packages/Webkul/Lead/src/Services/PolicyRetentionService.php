<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Webkul\Activity\Models\Activity;
use Webkul\Lead\Models\InsurancePolicy;

class PolicyRetentionService
{
    /**
     * Scan all policies, evaluate payment deadlines and transition ACA 90-day grace periods.
     */
    public function evaluateGracePeriods(): array
    {
        $today = Carbon::today();

        $policies = InsurancePolicy::whereIn('status', ['active', 'grace_period_1', 'grace_period_2_3'])
            ->whereNotNull('paid_to_date')
            ->get();

        $stats = [
            'total_evaluated' => $policies->count(),
            'active_kept' => 0,
            'grace_1_entered' => 0,
            'grace_critical_entered' => 0,
            'lapsed_cancelled' => 0,
            'alerts_created' => 0,
        ];

        foreach ($policies as $policy) {
            $paidTo = Carbon::parse($policy->paid_to_date);

            if ($paidTo->greaterThanOrEqualTo($today)) {
                // Payment is up to date
                if ($policy->status !== 'active') {
                    $policy->update([
                        'status' => 'active',
                        'grace_period_days' => 0,
                        'grace_period_start_date' => null,
                    ]);
                }
                $stats['active_kept']++;

                continue;
            }

            // Policy is overdue
            $overdueDays = (int) $paidTo->diffInDays($today, false);
            $startDate = $policy->grace_period_start_date ?: $paidTo->toDateString();

            if ($overdueDays <= 30) {
                // Month 1: Grace Period 1
                $policy->update([
                    'status' => 'grace_period_1',
                    'grace_period_days' => $overdueDays,
                    'grace_period_start_date' => $startDate,
                ]);
                $stats['grace_1_entered']++;
            } elseif ($overdueDays <= 90) {
                // Month 2-3: Critical Grace Period (claims pended, chargeback risk)
                $policy->update([
                    'status' => 'grace_period_2_3',
                    'grace_period_days' => $overdueDays,
                    'grace_period_start_date' => $startDate,
                ]);
                $stats['grace_critical_entered']++;

                // Alert the writing agent
                if ($this->createGracePeriodAlertActivity($policy, $overdueDays)) {
                    $stats['alerts_created']++;
                }
            } else {
                // > 90 Days: Terminated retroactive to month 1
                $policy->update([
                    'status' => 'cancelled',
                    'grace_period_days' => $overdueDays,
                    'notes' => trim(($policy->notes ?: '')."\n[".now()->toDateString().'] Póliza cancelada por falta de pago al superar los 90 días de gracia federal.'),
                ]);
                $stats['lapsed_cancelled']++;
            }
        }

        return $stats;
    }

    /**
     * Compute Book of Business retention metrics, persistency rate and premium volume.
     */
    public function getRetentionMetrics(?int $userId = null): array
    {
        $query = InsurancePolicy::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $allPolicies = $query->get();
        $total = $allPolicies->count();

        $active = $allPolicies->where('status', 'active');
        $grace1 = $allPolicies->where('status', 'grace_period_1');
        $graceCritical = $allPolicies->where('status', 'grace_period_2_3');
        $cancelled = $allPolicies->where('status', 'cancelled');
        $renewed = $allPolicies->where('status', 'renewed');

        $inForceCount = $active->count();
        $graceCount = $grace1->count() + $graceCritical->count();
        $cancelledCount = $cancelled->count();

        // Persistency Rate %: Active / (Total - Renewed) or 100% if empty
        $eligibleForPersistency = max(1, $total);
        $persistencyRate = round(($inForceCount / $eligibleForPersistency) * 100, 1);

        $grossVolume = $active->sum('gross_premium');
        $netVolume = $active->sum('net_premium');
        $coveredLives = $active->sum('members_count');

        $premiumAtRisk = $grace1->sum('net_premium') + $graceCritical->sum('net_premium');

        // Carrier breakdown
        $carrierBreakdown = $allPolicies->groupBy('carrier_name')->map(function ($group, $carrier) {
            return [
                'carrier' => $carrier,
                'total' => $group->count(),
                'active' => $group->where('status', 'active')->count(),
                'grace' => $group->whereIn('status', ['grace_period_1', 'grace_period_2_3'])->count(),
                'monthly_net' => round($group->where('status', 'active')->sum('net_premium'), 2),
            ];
        })->values()->toArray();

        return [
            'total_policies' => $total,
            'in_force_count' => $inForceCount,
            'covered_lives_count' => $coveredLives,
            'grace_count' => $graceCount,
            'grace_critical_count' => $graceCritical->count(),
            'cancelled_count' => $cancelledCount,
            'renewed_count' => $renewed->count(),
            'persistency_rate' => $persistencyRate,
            'gross_monthly_volume' => round($grossVolume, 2),
            'net_monthly_volume' => round($netVolume, 2),
            'premium_at_risk' => round($premiumAtRisk, 2),
            'carrier_breakdown' => $carrierBreakdown,
        ];
    }

    /**
     * Create an urgent alert activity for the agent when a policy enters critical grace period.
     */
    protected function createGracePeriodAlertActivity(InsurancePolicy $policy, int $overdueDays): bool
    {
        $existing = Activity::whereHas('leads', fn ($q) => $q->where('leads.id', $policy->lead_id))
            ->where('title', 'like', "%Período de Gracia: Póliza {$policy->policy_number}%")
            ->where('is_done', 0)
            ->exists();

        if ($existing) {
            return false;
        }

        $beneficiary = $policy->person?->name ?: ($policy->lead?->person?->name ?: 'Titular');

        try {
            $activity = Activity::create([
                'title' => "🚨 Período de Gracia: Póliza {$policy->policy_number} ({$policy->carrier_name})",
                'type' => 'call',
                'comment' => "URGENTE: La póliza de salud de {$beneficiary} lleva {$overdueDays} días en período de gracia por falta de pago. Riesgo inminente de cancelación retroactiva y contracargo (clawback). Contactar al cliente de inmediato.",
                'schedule_from' => Carbon::now(),
                'schedule_to' => Carbon::now()->addHours(24),
                'is_done' => 0,
                'user_id' => $policy->user_id ?: 1,
            ]);

            if ($policy->lead_id) {
                $activity->leads()->attach($policy->lead_id);
            }

            if ($policy->person_id) {
                $activity->persons()->attach($policy->person_id);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
