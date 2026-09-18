<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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

        return view('admin::consent.portal', compact('consent'));
    }

    /**
     * Process digital signature submission.
     */
    public function sign(Request $request, string $token)
    {
        $consent = LeadConsent::where('token', $token)->firstOrFail();

        if ($consent->status === 'signed') {
            return response()->json([
                'success' => false,
                'message' => 'Este documento ya fue firmado previamente.',
            ], 422);
        }

        $request->validate([
            'signature_data' => 'required|string',
            'client_name' => 'nullable|string|max:150',
        ]);

        $signatureData = $request->input('signature_data');

        // Basic verification of base64 image data
        if (! str_starts_with($signatureData, 'data:image/png;base64,') && ! str_starts_with($signatureData, 'data:image/jpeg;base64,')) {
            return response()->json([
                'success' => false,
                'message' => 'Formato de firma digital inválido.',
            ], 422);
        }

        $consent->update([
            'status' => 'signed',
            'signature_data' => $signatureData,
            'signed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'client_name' => $request->input('client_name') ?: $consent->client_name,
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Consentimiento firmado y registrado legalmente con éxito!',
            'redirect_url' => route('consent.portal.show', $consent->token),
        ]);
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
