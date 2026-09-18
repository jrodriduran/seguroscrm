<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Webkul\Lead\Models\AgencyHierarchy;
use Webkul\Lead\Models\InsuranceCommission;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\PolicyOverrideDistribution;
use Webkul\User\Models\User;

class AgencyOverrideService
{
    /**
     * Compute and distribute multi-tier overrides for an issued policy.
     */
    public function distributeOverridesForPolicy(InsurancePolicy $policy, ?InsuranceCommission $commission = null, ?string $periodMonth = null): array
    {
        $writingAgentId = $policy->user_id;
        if (! $writingAgentId) {
            return [];
        }

        $period = $periodMonth ?: Carbon::now()->format('Y-m');
        $uplineChain = AgencyHierarchy::getUplineChain($writingAgentId);

        $createdDistributions = [];
        $members = max(1, (int) $policy->members_count);

        foreach ($uplineChain as $tier) {
            $pmpmRate = (float) $tier['override_pmpm'];
            $pctRate = (float) $tier['override_percentage'];

            if ($pmpmRate > 0) {
                $amount = round($members * $pmpmRate, 2);
            } elseif ($pctRate > 0) {
                $gross = (float) $policy->gross_premium;
                $amount = round($gross * ($pctRate / 100), 2);
            } else {
                // Default minimum tier override of $2.00 PMPM
                $pmpmRate = 2.00;
                $amount = round($members * $pmpmRate, 2);
            }

            $dist = PolicyOverrideDistribution::updateOrCreate(
                [
                    'policy_id' => $policy->id,
                    'beneficiary_user_id' => $tier['beneficiary_user_id'],
                    'period_month' => $period,
                ],
                [
                    'commission_id' => $commission?->id,
                    'writing_agent_id' => $writingAgentId,
                    'tier_level' => $tier['level'],
                    'tier_name' => $tier['tier_name'],
                    'rate_per_member' => $pmpmRate,
                    'members_count' => $members,
                    'override_amount' => $amount,
                    'status' => 'approved',
                ]
            );

            $createdDistributions[] = $dist;
        }

        return $createdDistributions;
    }

    /**
     * Build full agency organizational tree with production & override metrics.
     */
    public function getHierarchyTree(): array
    {
        $users = User::all();
        $hierarchies = AgencyHierarchy::with(['user', 'parentUser'])->get()->keyBy('user_id');

        // Identify root users (no parent_user_id or parent not in users)
        $tree = [];

        foreach ($users as $user) {
            $hierarchy = $hierarchies->get($user->id);
            $hasParent = $hierarchy && $hierarchy->parent_user_id;

            if (! $hasParent) {
                $tree[] = $this->buildNode($user, $hierarchies);
            }
        }

        return $tree;
    }

    /**
     * Recursively build hierarchy node for an agent and their downlines.
     */
    protected function buildNode(User $user, $hierarchies): array
    {
        $hierarchy = $hierarchies->get($user->id);

        // Calculate personal production
        $personalPolicies = InsurancePolicy::where('user_id', $user->id)->where('status', 'active')->get();
        $personalActiveCount = $personalPolicies->count();
        $personalLives = $personalPolicies->sum('members_count');

        // Direct downline agents
        $downlineHierarchies = AgencyHierarchy::where('parent_user_id', $user->id)->get();
        $children = [];
        $downlinePoliciesCount = 0;
        $downlineLives = 0;

        foreach ($downlineHierarchies as $dh) {
            $childUser = User::find($dh->user_id);
            if ($childUser) {
                $childNode = $this->buildNode($childUser, $hierarchies);
                $children[] = $childNode;
                $downlinePoliciesCount += ($childNode['personal_policies_count'] + $childNode['downline_policies_count']);
                $downlineLives += ($childNode['personal_lives_count'] + $childNode['downline_lives_count']);
            }
        }

        // Total monthly override revenue earned by this user
        $currentMonth = Carbon::now()->format('Y-m');
        $monthlyOverrides = (float) PolicyOverrideDistribution::where('beneficiary_user_id', $user->id)
            ->where('period_month', $currentMonth)
            ->sum('override_amount');

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'agency_tier' => $hierarchy?->agency_tier ?: 'producer',
            'tier_label' => $hierarchy?->tier_label ?: 'Agente Productor',
            'sub_agency_name' => $hierarchy?->sub_agency_name,
            'npn_number' => $hierarchy?->npn_number,
            'contract_level_percentage' => (float) ($hierarchy?->contract_level_percentage ?: 70.0),
            'override_pmpm' => (float) ($hierarchy?->override_pmpm ?: 0.0),
            'personal_policies_count' => $personalActiveCount,
            'personal_lives_count' => $personalLives,
            'downline_policies_count' => $downlinePoliciesCount,
            'downline_lives_count' => $downlineLives,
            'total_team_lives' => $personalLives + $downlineLives,
            'monthly_overrides_earned' => round($monthlyOverrides, 2),
            'children' => $children,
        ];
    }
}
