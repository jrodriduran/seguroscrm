<?php

namespace Webkul\Admin\Http\Controllers\Lead;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadConsent;

class ConsentController extends Controller
{
    use PDFHandler;

    /**
     * Get or initialize the consent record for a lead.
     */
    public function get(int $leadId): JsonResponse
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);

        $consent = LeadConsent::where('lead_id', $lead->id)->first();

        if (! $consent) {
            $clientName = $lead->person?->name ?: $lead->title;
            $clientPhone = collect($lead->person?->contact_numbers ?? [])->first()['value'] ?? null;
            $clientEmail = collect($lead->person?->emails ?? [])->first()['value'] ?? null;

            $agentName = $lead->user?->name ?: (auth()->guard('user')->user()?->name ?: 'Agente Certificado ACA');
            $agentNpn = '19845210'; // Default NPN or placeholder
            $agencyName = 'Seguros CRM - Marketplace Agency';

            $consentText = LeadConsent::getDefaultConsentText($clientName, $agentName, $agentNpn, $agencyName);

            $consent = LeadConsent::create([
                'lead_id' => $lead->id,
                'person_id' => $lead->person_id,
                'user_id' => $lead->user_id ?: auth()->guard('user')->id(),
                'token' => Str::random(36),
                'status' => 'pending',
                'client_name' => $clientName,
                'client_phone' => $clientPhone,
                'client_email' => $clientEmail,
                'agent_name' => $agentName,
                'agent_npn' => $agentNpn,
                'agency_name' => $agencyName,
                'consent_text' => $consentText,
            ]);
        }

        return response()->json([
            'success' => true,
            'consent' => $consent,
            'public_url' => $consent->public_url,
            'whatsapp_message' => $consent->whatsapp_message,
        ]);
    }

    /**
     * Get WhatsApp invitation link and payload.
     */
    public function getWhatsAppLink(int $leadId): JsonResponse
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);
        $consent = LeadConsent::where('lead_id', $lead->id)->first();

        if (! $consent) {
            // trigger creation
            $res = $this->get($leadId);
            $data = $res->getData(true);
            $consent = LeadConsent::find($data['consent']['id']);
        }

        $phone = $consent->client_phone ?: collect($lead->person?->contact_numbers ?? [])->first()['value'] ?? '';
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);

        $whatsappUrl = 'https://api.whatsapp.com/send?phone='.$cleanPhone.'&text='.urlencode($consent->whatsapp_message);

        return response()->json([
            'success' => true,
            'phone' => $phone,
            'clean_phone' => $cleanPhone,
            'message' => $consent->whatsapp_message,
            'public_url' => $consent->public_url,
            'whatsapp_url' => $whatsappUrl,
        ]);
    }

    /**
     * Revoke an active or pending consent.
     */
    public function revoke(Request $request, int $leadId): JsonResponse
    {
        $consent = LeadConsent::where('lead_id', $leadId)->firstOrFail();
        $reason = $request->input('reason', 'Revocado formalmente por el titular');

        $consent->update([
            'status' => 'revoked',
        ]);

        // Update latest signed version without deleting signature or legal audit data
        $latestSignedVersion = $consent->versions()
            ->where('status', 'signed')
            ->first();

        if ($latestSignedVersion) {
            $latestSignedVersion->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revocation_reason' => $reason,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'El consentimiento ha sido revocado formalmente. El registro histórico y firma previa se conservan inmutables para auditoría CMS (10 años).',
            'consent' => $consent->fresh(['versions']),
        ]);
    }

    /**
     * Reset / create a fresh consent link if requested, preserving past signed versions.
     */
    public function regenerate(int $leadId): JsonResponse
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);
        $consent = LeadConsent::where('lead_id', $leadId)->first();

        $clientName = $lead->person?->name ?: $lead->title;
        $agentName = $lead->user?->name ?: (auth()->guard('user')->user()?->name ?: 'Agente Certificado ACA');
        $agentNpn = $lead->user?->npn ?: '19845210';
        $agencyName = 'Seguros CRM - Marketplace Agency';

        if ($consent) {
            // If currently signed, ensure previous signature is safely archived as superseded
            if ($consent->status === 'signed' && $consent->signature_data) {
                $alreadyArchived = $consent->versions()
                    ->where('signed_at', $consent->signed_at)
                    ->exists();

                if (! $alreadyArchived) {
                    $vNum = ((int) $consent->versions()->max('version_number')) + 1;
                    $fileHash = \Webkul\Lead\Models\LeadConsentVersion::generateHash(
                        (string) $consent->signature_data,
                        (string) $consent->consent_text,
                        (string) $consent->client_name,
                        $consent->signed_at?->toIso8601String() ?: now()->toIso8601String(),
                        $consent->ip_address
                    );

                    \Webkul\Lead\Models\LeadConsentVersion::create([
                        'lead_consent_id' => $consent->id,
                        'lead_id' => $consent->lead_id,
                        'version_number' => $vNum,
                        'status' => 'superseded',
                        'client_name' => $consent->client_name,
                        'client_phone' => $consent->client_phone,
                        'client_email' => $consent->client_email,
                        'agent_name' => $consent->agent_name,
                        'agent_npn' => $consent->agent_npn,
                        'agency_name' => $consent->agency_name,
                        'consent_text' => $consent->consent_text,
                        'signature_data' => $consent->signature_data,
                        'signed_at' => $consent->signed_at,
                        'ip_address' => $consent->ip_address,
                        'user_agent' => $consent->user_agent,
                        'pdf_path' => $consent->pdf_path,
                        'file_hash' => $fileHash,
                    ]);
                } else {
                    $consent->versions()
                        ->where('status', 'signed')
                        ->update(['status' => 'superseded']);
                }
            }

            $consent->update([
                'token' => Str::random(36),
                'status' => 'pending',
                'signature_data' => null,
                'signed_at' => null,
                'ip_address' => null,
                'user_agent' => null,
                'agent_npn' => $agentNpn,
                'consent_text' => LeadConsent::getDefaultConsentText($clientName, $agentName, $agentNpn, $agencyName),
            ]);
        } else {
            return $this->get($leadId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nuevo enlace de consentimiento generado exitosamente. Las firmas previas han sido archivadas para auditoría.',
            'consent' => $consent,
            'public_url' => $consent->public_url,
            'whatsapp_message' => $consent->whatsapp_message,
        ]);
    }

    /**
     * Export complete CMS 10-year audit trail for this consumer.
     */
    public function auditExport(int $leadId): JsonResponse
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);
        $consent = LeadConsent::with('versions')->where('lead_id', $leadId)->firstOrFail();

        $auditTrail = $consent->versions->map(function ($ver) {
            return [
                'version' => $ver->version_number,
                'status' => $ver->status,
                'client_name' => $ver->client_name,
                'agent_name' => $ver->agent_name,
                'agent_npn' => $ver->agent_npn,
                'signed_at' => $ver->signed_at?->toIso8601String(),
                'ip_address' => $ver->ip_address,
                'user_agent' => $ver->user_agent,
                'sha256_hash' => $ver->file_hash,
                'integrity_verified' => $ver->verifyIntegrity(),
                'revoked_at' => $ver->revoked_at?->toIso8601String(),
                'revocation_reason' => $ver->revocation_reason,
            ];
        });

        return response()->json([
            'success' => true,
            'regulation' => 'CMS 45 CFR § 155.220 (10-Year Record Retention Rule)',
            'consumer' => [
                'name' => $consent->client_name,
                'phone' => $consent->client_phone,
                'email' => $consent->client_email,
                'lead_id' => $lead->id,
            ],
            'current_status' => $consent->status,
            'total_versions_archived' => $consent->versions->count(),
            'audit_trail' => $auditTrail,
        ]);
    }

    /**
     * Compliance print view
     */
    public function printCertificate(int $leadId)
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);
        $consent = LeadConsent::where('lead_id', $lead->id)->firstOrFail();

        return view('admin::consent.certificate', [
            'lead' => $lead,
            'consent' => $consent,
            'isPdf' => false,
        ]);
    }

    /**
     * Download Compliance Certificate as direct PDF
     */
    public function downloadCertificatePdf(int $leadId)
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);
        $consent = LeadConsent::where('lead_id', $lead->id)->firstOrFail();

        $html = view('admin::consent.certificate', [
            'lead' => $lead,
            'consent' => $consent,
            'isPdf' => true,
        ])->render();

        $clientSlug = Str::slug($lead->person?->name ?: $lead->title ?: 'cliente');
        $fileName = 'Certificado_Consentimiento_CMS_'.$lead->id.'_'.$clientSlug;

        return $this->downloadPDF($html, $fileName);
    }
}
