<?php

namespace Webkul\Insurance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Insurance\Exceptions\HealthSherpaFailure;
use Webkul\Insurance\Http\Requests\HealthSherpaQuoteRequest;
use Webkul\Insurance\Services\HealthSherpaOne;
use Webkul\Lead\Models\LeadConsent;
use Webkul\Lead\Repositories\LeadRepository;

class HealthSherpaController extends Controller
{
    public function __construct(protected LeadRepository $leads, protected HealthSherpaOne $provider) {}

    public function readiness(): JsonResponse
    {
        return response()->json(['data' => $this->provider->readiness()]);
    }

    public function counties(Request $request): JsonResponse
    {
        $data = $request->validate(['zip_code' => ['required', 'string', 'regex:/^\d{5}$/']]);

        return $this->providerResponse(fn () => $this->provider->counties($data['zip_code']));
    }

    public function quotes(HealthSherpaQuoteRequest $request, int $leadId): JsonResponse
    {
        $lead = $this->leads->findOrFail($leadId);
        $this->preventUnauthorizedAccess($lead->user_id);

        if (! LeadConsent::where('lead_id', $lead->id)->where('status', 'signed')->exists()) {
            throw ValidationException::withMessages([
                'consent' => trans('admin::app.healthsherpa.consent-required'),
            ]);
        }

        $payload = $request->payload();
        $members = $lead->householdMembers()->where('is_applying_coverage', true)->pluck('id')
            ->map(fn ($id) => 'member-'.$id)->push('person-'.$lead->person_id);

        foreach ($payload['household']['applicants'] as $applicant) {
            if (! $members->contains($applicant['member_id'])
                || ($applicant['relationship'] === 'primary') !== ($applicant['member_id'] === 'person-'.$lead->person_id)) {
                throw ValidationException::withMessages([
                    'household.applicants' => trans('admin::app.healthsherpa.member-mismatch'),
                ]);
            }
        }

        return $this->providerResponse(fn () => $this->provider->quotes($payload));
    }

    // Keep existing route names working, while preventing the fabricated legacy handoff.
    public function exportPayload(int $leadId): JsonResponse
    {
        $lead = $this->leads->findOrFail($leadId);
        $this->preventUnauthorizedAccess($lead->user_id);

        return response()->json(['message' => trans('admin::app.healthsherpa.bridge-retired')], 422);
    }

    public function redirectToEnrollment(int $leadId): JsonResponse
    {
        $lead = $this->leads->findOrFail($leadId);
        $this->preventUnauthorizedAccess($lead->user_id);

        return response()->json(['message' => trans('admin::app.healthsherpa.enrollment-pending')], 503);
    }

    private function providerResponse(callable $operation): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $operation()]);
        } catch (HealthSherpaFailure $error) {
            $response = response()->json([
                'success' => false,
                'message' => trans('admin::app.healthsherpa.errors.'.$error->reason),
            ], $error->httpStatus);

            if ($error->retryAfter !== null) {
                $response->header('Retry-After', (string) $error->retryAfter);
            }

            return $response;
        }
    }
}
