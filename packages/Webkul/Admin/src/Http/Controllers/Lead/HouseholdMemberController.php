<?php

namespace Webkul\Admin\Http\Controllers\Lead;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Repositories\LeadRepository;

class HouseholdMemberController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected LeadRepository $leadRepository) {}

    /**
     * List all household members for a lead.
     */
    public function index(int $leadId): JsonResponse
    {
        $lead = $this->leadRepository->findOrFail($leadId);

        return response()->json([
            'data' => $lead->householdMembers()->orderBy('id', 'asc')->get(),
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
}
