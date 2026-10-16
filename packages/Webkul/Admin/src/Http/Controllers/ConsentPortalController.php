<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadConsent;

class ConsentPortalController extends Controller
{
    /**
     * Display the mobile-friendly touch consent signing portal.
     */
    public function show(string $token)
    {
        $consent = LeadConsent::where('token', $token)->firstOrFail();

        if ($consent->status === 'signed') {
            return view('admin::consent.signed', compact('consent'));
        }

        abort_unless($consent->status === 'pending', 410);
        abort_if($consent->agency_name === 'Seguros CRM - Marketplace Agency', 410);

        return view('admin::consent.portal', compact('consent'));
    }

    /**
     * Process digital signature submission.
     */
    public function sign(Request $request, string $token)
    {
        return DB::transaction(function () use ($request, $token) {
            $leadId = LeadConsent::where('token', $token)->value('lead_id');
            Lead::whereKey($leadId)->lockForUpdate()->firstOrFail();
            $consent = LeadConsent::where('token', $token)->lockForUpdate()->firstOrFail();

            if ($consent->status !== 'pending' || $consent->agency_name === 'Seguros CRM - Marketplace Agency') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este documento ya fue firmado previamente.',
                ], 422);
            }

            $request->validate([
                'signature_data' => 'nullable|string',
                'signature' => 'nullable|string',
                'client_name' => 'nullable|string|max:150',
            ]);

            $signatureData = $request->input('signature_data') ?: $request->input('signature');
            if (! $signatureData) {
                return response()->json(['message' => 'The signature data field is required.'], 422);
            }

            // Basic verification of base64 image data
            if (! str_starts_with($signatureData, 'data:image/png;base64,') && ! str_starts_with($signatureData, 'data:image/jpeg;base64,')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de firma digital inválido.',
                ], 422);
            }

            if ($request->filled('client_name')) {
                $consent->client_name = $request->input('client_name');
                $consent->save();
            }

            $consent->recordSignature(
                $signatureData,
                $request->ip(),
                substr((string) $request->userAgent(), 0, 255)
            );

            return response()->json([
                'success' => true,
                'message' => '¡Consentimiento firmado y registrado legalmente con éxito!',
                'redirect_url' => route('consent.portal.show', $consent->token),
            ]);
        });
    }

    /**
     * Show legal receipt / proof of consent
     */
    public function receipt(string $token)
    {
        $consent = LeadConsent::where('token', $token)->firstOrFail();

        return view('admin::consent.signed', compact('consent'));
    }
}
