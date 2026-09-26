<?php

namespace Webkul\Admin\Http\Controllers\Lead;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Services\FplCalculatorService;

class HouseholdMemberController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected LeadRepository $leadRepository,
        protected FplCalculatorService $fplCalculatorService
    ) {}

    /**
     * List all household members for a lead with PII protection.
     */
    public function index(int $leadId): JsonResponse
    {
        $lead = $this->leadRepository->findOrFail($leadId);
        $canViewPii = bouncer()->hasPermission('leads.view_sensitive_pii');

        $members = $lead->householdMembers()->orderBy('id', 'asc')->get()->map(function ($member) use ($canViewPii) {
            $data = $member->toArray();
            $data['can_reveal_ssn'] = $canViewPii;
            $data['is_revealed'] = false;
            // Always mask in initial list for HIPAA/PII safety
            $data['ssn_itin'] = $member->masked_ssn;

            return $data;
        });

        return response()->json([
            'data' => $members,
            'can_view_pii' => $canViewPii,
        ]);
    }

    /**
     * Reveal decrypted SSN/ITIN for authorized compliance officer/agent.
     */
    public function revealPii(Request $request, int $leadId, int $id): JsonResponse
    {
        if (! bouncer()->hasPermission('leads.view_sensitive_pii')) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para consultar información personal identificable (PII/SSN).',
            ], 403);
        }

        $lead = $this->leadRepository->findOrFail($leadId);
        $member = $lead->householdMembers()->findOrFail($id);

        return response()->json([
            'success' => true,
            'id' => $member->id,
            'full_ssn' => $member->ssn_itin,
            'masked_ssn' => $member->masked_ssn,
        ]);
    }

    /**
     * Store a newly created household member.
     */
    public function store(Request $request, int $leadId): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'ssn_itin' => 'nullable|string|max:50',
            'immigration_status' => 'nullable|string|max:100',
            'is_applying_coverage' => 'nullable|boolean',
            'tobacco_user' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $lead = $this->leadRepository->findOrFail($leadId);

        $member = $lead->householdMembers()->create([
            'name' => $request->input('name'),
            'relationship' => $request->input('relationship'),
            'date_of_birth' => $request->input('date_of_birth'),
            'gender' => $request->input('gender'),
            'ssn_itin' => $request->input('ssn_itin'),
            'immigration_status' => $request->input('immigration_status'),
            'is_applying_coverage' => $request->boolean('is_applying_coverage', true),
            'tobacco_user' => $request->boolean('tobacco_user', false),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'message' => trans('admin::insurance.household.created_success'),
            'data' => $member,
        ]);
    }

    /**
     * Update an existing household member.
     */
    public function update(Request $request, int $leadId, int $id): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'ssn_itin' => 'nullable|string|max:50',
            'immigration_status' => 'nullable|string|max:100',
            'is_applying_coverage' => 'nullable|boolean',
            'tobacco_user' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $lead = $this->leadRepository->findOrFail($leadId);
        $member = $lead->householdMembers()->findOrFail($id);

        $member->update([
            'name' => $request->input('name'),
            'relationship' => $request->input('relationship'),
            'date_of_birth' => $request->input('date_of_birth'),
            'gender' => $request->input('gender'),
            'ssn_itin' => $request->input('ssn_itin'),
            'immigration_status' => $request->input('immigration_status'),
            'is_applying_coverage' => $request->boolean('is_applying_coverage', true),
            'tobacco_user' => $request->boolean('tobacco_user', false),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'message' => trans('admin::insurance.household.updated_success'),
            'data' => $member->fresh(),
        ]);
    }

    /**
     * Remove a household member.
     */
    public function destroy(int $leadId, int $id): JsonResponse
    {
        $lead = $this->leadRepository->findOrFail($leadId);
        $member = $lead->householdMembers()->findOrFail($id);

        $member->delete();

        return response()->json([
            'message' => trans('admin::insurance.household.deleted_success'),
        ]);
    }

    /**
     * Get or calculate current FPL eligibility and ACA subsidy estimates for the lead.
     */
    public function getFplEligibility(Request $request, int $leadId): JsonResponse
    {
        $lead = $this->leadRepository->findOrFail($leadId);
        $taxYear = (int) $request->input('tax_year', 2026);

        // Fetch existing tax household record if available
        $taxHousehold = $lead->taxHouseholds()->where('tax_year', $taxYear)->first();

        $householdCount = $lead->householdMembers()->count();
        // Base household size: 1 primary subscriber + members count
        $defaultSize = max(1, $householdCount + 1);
        $defaultApplying = max(1, $lead->householdMembers()->where('is_applying_coverage', 1)->count() + 1);

        $stateCode = $taxHousehold?->state_code ?: 'FL';
        $annualIncome = $taxHousehold ? (float) $taxHousehold->projected_annual_income : 0.0;
        $size = $taxHousehold ? (int) $taxHousehold->household_size : $defaultSize;

        $calc = $this->fplCalculatorService->calculate(
            householdSize: $size,
            annualIncome: $annualIncome,
            stateCode: $stateCode,
            taxYear: $taxYear,
            applyingMembers: $defaultApplying
        );

        return response()->json([
            'success' => true,
            'has_saved_record' => (bool) $taxHousehold,
            'tax_household' => $taxHousehold,
            'calculation' => $calc,
        ]);
    }

    /**
     * Save/update projected income and tax household eligibility.
     */
    public function saveFplEligibility(Request $request, int $leadId): JsonResponse
    {
        $this->validate($request, [
            'projected_annual_income' => 'required|numeric|min:0',
            'household_size'          => 'nullable|integer|min:1|max:20',
            'tax_year'                => 'nullable|integer',
            'state_code'              => 'nullable|string|max:2',
            'notes'                   => 'nullable|string',
        ]);

        $lead = $this->leadRepository->findOrFail($leadId);
        $taxYear = (int) $request->input('tax_year', 2026);
        $stateCode = strtoupper((string) $request->input('state_code', 'FL'));

        $householdCount = $lead->householdMembers()->count();
        $defaultSize = max(1, $householdCount + 1);
        $size = (int) $request->input('household_size', $defaultSize);
        $annualIncome = (float) $request->input('projected_annual_income');

        $applyingCount = max(1, $lead->householdMembers()->where('is_applying_coverage', 1)->count() + 1);

        $calc = $this->fplCalculatorService->calculate(
            householdSize: $size,
            annualIncome: $annualIncome,
            stateCode: $stateCode,
            taxYear: $taxYear,
            applyingMembers: $applyingCount
        );

        $record = $lead->taxHouseholds()->updateOrCreate(
            ['tax_year' => $taxYear],
            [
                'state_code'                  => $stateCode,
                'household_size'              => $size,
                'projected_annual_income'     => $annualIncome,
                'fpl_guideline_threshold'     => $calc['fpl_guideline_threshold'],
                'fpl_percentage'              => $calc['fpl_percentage'],
                'fpl_category'                => $calc['fpl_category'],
                'csr_tier'                    => $calc['csr_tier'],
                'applicable_percentage'       => $calc['applicable_percentage'],
                'max_annual_contribution'     => $calc['max_annual_contribution'],
                'max_monthly_contribution'    => $calc['max_monthly_contribution'],
                'estimated_benchmark_premium' => $calc['estimated_benchmark_premium'],
                'estimated_monthly_aptc'      => $calc['estimated_monthly_aptc'],
                'estimated_net_premium'       => $calc['estimated_net_premium'],
                'notes'                       => $request->input('notes'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => trans('admin::insurance.household.fpl_saved_success'),
            'tax_household' => $record->fresh(),
            'calculation' => $calc,
        ]);
    }

    /**
     * Preview on-the-fly FPL and CSR calculations without database persistence.
     */
    public function previewFplCalculation(Request $request, int $leadId): JsonResponse
    {
        $this->validate($request, [
            'projected_annual_income' => 'required|numeric|min:0',
            'household_size'          => 'required|integer|min:1|max:20',
            'tax_year'                => 'nullable|integer',
            'state_code'              => 'nullable|string|max:2',
            'custom_benchmark'        => 'nullable|numeric|min:0',
        ]);

        $annualIncome = (float) $request->input('projected_annual_income');
        $size = (int) $request->input('household_size');
        $taxYear = (int) $request->input('tax_year', 2026);
        $stateCode = (string) $request->input('state_code', 'FL');
        $customBenchmark = $request->has('custom_benchmark') ? (float) $request->input('custom_benchmark') : null;

        $calc = $this->fplCalculatorService->calculate(
            householdSize: $size,
            annualIncome: $annualIncome,
            stateCode: $stateCode,
            taxYear: $taxYear,
            customBenchmark: $customBenchmark
        );

        return response()->json([
            'success' => true,
            'calculation' => $calc,
        ]);
    }
}
