<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Services\PolicyRenewalService;

class PolicyRenewalController extends Controller
{
    public function __construct(
        protected PolicyRenewalService $renewalService
    ) {}

    /**
     * Get OEP renewal cohort and retention KPIs.
     */
    public function hub(Request $request): JsonResponse
    {
        $currentMonth = now()->month;
        $defaultPlanYear = $currentMonth >= 10 ? now()->year + 1 : now()->year;
        $planYear = (int) $request->get('plan_year', $defaultPlanYear);

        $data = $this->renewalService->getOepRenewalCohort($planYear, $request->all());

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get year-over-year side-by-side comparison for a policy.
     */
    public function comparison(Request $request, int $id): JsonResponse
    {
        $policy = InsurancePolicy::with(['priorPolicy', 'renewedPolicies'])->findOrFail($id);

        // If client passes preview parameters, generate preview comparison
        if ($request->has('preview') && $request->boolean('preview')) {
            $comparison = $this->renewalService->previewComparison($policy, $request->all());

            return response()->json([
                'success' => true,
                'is_preview' => true,
                'comparison' => $comparison,
            ]);
        }

        // Check if policy is a renewed child (has priorPolicy)
        if ($policy->priorPolicy) {
            $comparison = $policy->getYearOverYearComparison();
        } elseif ($renewedChild = $policy->renewedPolicies->first()) {
            // Check if policy has a renewed child
            $comparison = $renewedChild->getYearOverYearComparison($policy);
        } else {
            // No renewal recorded yet, build baseline preview
            $comparison = [
                'prior_policy' => [
                    'id' => $policy->id,
                    'policy_number' => $policy->policy_number,
                    'carrier_name' => $policy->carrier_name,
                    'plan_name' => $policy->plan_name,
                    'metal_tier' => $policy->metal_tier,
                    'plan_year' => $policy->plan_year ?: ($policy->effective_date ? $policy->effective_date->year : now()->year),
                    'gross_premium' => (float) $policy->gross_premium,
                    'aptc_subsidy' => (float) $policy->aptc_subsidy,
                    'net_premium' => (float) $policy->net_premium,
                    'deductible' => (float) ($policy->deductible ?? 0),
                    'max_out_of_pocket' => (float) ($policy->max_out_of_pocket ?? 0),
                    'status' => $policy->status,
                ],
                'current_policy' => null,
                'variance' => null,
            ];
        }

        return response()->json([
            'success' => true,
            'is_preview' => false,
            'comparison' => $comparison,
        ]);
    }

    /**
     * Process Year-Over-Year OEP Renewal.
     */
    public function process(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'plan_year' => 'nullable|integer',
            'carrier_name' => 'required|string|max:100',
            'plan_name' => 'required|string|max:150',
            'metal_tier' => 'nullable|string|max:30',
            'network_type' => 'nullable|string|max:30',
            'gross_premium' => 'required|numeric|min:0',
            'aptc_subsidy' => 'required|numeric|min:0',
            'net_premium' => 'nullable|numeric|min:0',
            'deductible' => 'nullable|numeric|min:0',
            'max_out_of_pocket' => 'nullable|numeric|min:0',
            'effective_date' => 'nullable|date',
            'renewal_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $priorPolicy = InsurancePolicy::findOrFail($id);
        $userId = auth()->guard('user')->id() ?: $priorPolicy->user_id;

        $newPolicy = $this->renewalService->processRenewal($priorPolicy, $validated, $userId);
        $comparison = $newPolicy->getYearOverYearComparison($priorPolicy);

        return response()->json([
            'success' => true,
            'message' => "Renovación OEP para año {$newPolicy->plan_year} procesada con éxito. Póliza vinculada #{$newPolicy->policy_number}.",
            'policy' => $newPolicy,
            'comparison' => $comparison,
        ]);
    }
}
