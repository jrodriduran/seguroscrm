<?php

namespace Webkul\Admin\Http\Controllers\Medicare;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadMedicareSoa;

class MedicareSoaPortalController extends Controller
{
    /**
     * Show beneficiary electronic Scope of Appointment signing portal.
     */
    public function show(string $token): View
    {
        $soa = LeadMedicareSoa::where('token', $token)->firstOrFail();

        abort_if($soa->status === 'pending' && $soa->agency_name === 'Seguros CRM - Medicare Division', 410);

        return view('admin::medicare.soa.portal', compact('soa'));
    }

    /**
     * Process beneficiary signature.
     */
    public function sign(Request $request, string $token): JsonResponse
    {
        return DB::transaction(function () use ($request, $token) {
            $leadId = LeadMedicareSoa::where('token', $token)->value('lead_id');
            Lead::whereKey($leadId)->lockForUpdate()->firstOrFail();
            $soa = LeadMedicareSoa::where('token', $token)->lockForUpdate()->firstOrFail();

            if ($soa->status !== 'pending' || $soa->signature_data || $soa->signed_at
                || $soa->agency_name === 'Seguros CRM - Medicare Division') {
                return response()->json(['message' => trans('admin::app.insurance-identity.signed-soa')], 422);
            }

            $request->validate([
                'signature' => 'nullable|string',
                'signature_data' => 'nullable|string',
                'discuss_medicare_advantage' => 'nullable|boolean',
                'discuss_prescription_drug' => 'nullable|boolean',
                'discuss_medigap' => 'nullable|boolean',
                'discuss_dental_vision' => 'nullable|boolean',
                'discuss_hospital_indemnity' => 'nullable|boolean',
            ]);

            $signature = $request->input('signature') ?: $request->input('signature_data');
            if (! $signature) {
                return response()->json(['message' => 'The signature field is required.'], 422);
            }

            $now = Carbon::now();

            $soa->update([
                'status' => 'signed',
                'signature_data' => $signature,
                'signed_at' => $now,
                'appointment_eligible_at' => $now->copy()->addHours(48), // Official CMS 48-Hour Waiting Rule
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'discuss_medicare_advantage' => $request->boolean('discuss_medicare_advantage', true),
                'discuss_prescription_drug' => $request->boolean('discuss_prescription_drug', true),
                'discuss_medigap' => $request->boolean('discuss_medigap', false),
                'discuss_dental_vision' => $request->boolean('discuss_dental_vision', false),
                'discuss_hospital_indemnity' => $request->boolean('discuss_hospital_indemnity', false),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Firma registrada exitosamente conforme a las normas de Medicare CMS.',
                'redirect_url' => route('medicare.soa.signed', $soa->token),
            ]);
        });
    }

    /**
     * Thank you / Confirmation page.
     */
    public function signed(string $token): View
    {
        $soa = LeadMedicareSoa::where('token', $token)->firstOrFail();

        return view('admin::medicare.soa.signed', compact('soa'));
    }
}
