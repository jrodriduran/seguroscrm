<?php

namespace Webkul\Insurance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Lead\HouseholdMemberController;

class FamilyMemberController extends HouseholdMemberController
{
    protected function authorizeLead(int $leadId): void
    {
        $this->preventUnauthorizedAccess($this->leadRepository->findOrFail($leadId)->user_id);
    }

    protected function masked(JsonResponse $response): JsonResponse
    {
        $data = $response->getData(true);
        if (isset($data['data'])) {
            $data['data']['ssn_itin'] = $data['data']['masked_ssn'] ?? null;
        }

        return $response->setData($data);
    }

    public function index(int $leadId): JsonResponse
    {
        $this->authorizeLead($leadId);

        return parent::index($leadId);
    }

    public function store(Request $request, int $leadId): JsonResponse
    {
        $this->authorizeLead($leadId);

        return $this->masked(parent::store($request, $leadId));
    }

    public function update(Request $request, int $leadId, int $id): JsonResponse
    {
        $this->authorizeLead($leadId);
        $member = $this->leadRepository->findOrFail($leadId)->householdMembers()->findOrFail($id);
        // The existing edit form sends the masked value. Never replace the real value with it.
        if ($request->input('ssn_itin') === $member->masked_ssn) {
            $request->merge(['ssn_itin' => $member->ssn_itin]);
        }

        return $this->masked(parent::update($request, $leadId, $id));
    }

    public function revealPii(Request $request, int $leadId, int $id): JsonResponse
    {
        $this->authorizeLead($leadId);

        return parent::revealPii($request, $leadId, $id);
    }

    public function destroy(int $leadId, int $id): JsonResponse
    {
        $this->authorizeLead($leadId);
        $this->leadRepository->findOrFail($leadId)->householdMembers()->findOrFail($id);

        return response()->json(['message' => trans('admin::app.family-workflow.keep-history')], 422);
    }

    public function getFplEligibility(Request $request, int $leadId): JsonResponse
    {
        $this->authorizeLead($leadId);

        $response = parent::getFplEligibility($request, $leadId);
        $data = $response->getData(true);
        if (! $data['has_saved_record']) {
            // Missing income is unknown, not zero. Do not present default data as a client result.
            $data['calculation'] = null;
        }

        return $response->setData($data);
    }

    public function saveFplEligibility(Request $request, int $leadId): JsonResponse
    {
        $this->authorizeLead($leadId);

        return parent::saveFplEligibility($request, $leadId);
    }

    public function previewFplCalculation(Request $request, int $leadId): JsonResponse
    {
        $this->authorizeLead($leadId);

        return parent::previewFplCalculation($request, $leadId);
    }
}
