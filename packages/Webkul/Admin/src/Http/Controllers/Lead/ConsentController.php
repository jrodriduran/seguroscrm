<?php

namespace Webkul\Admin\Http\Controllers\Lead;

use Illuminate\Http\JsonResponse;
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
    public function revoke(int $leadId): JsonResponse
    {
        $consent = LeadConsent::where('lead_id', $leadId)->firstOrFail();

        $consent->update([
            'status' => 'revoked',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'El consentimiento ha sido revocado correctamente.',
            'consent' => $consent,
        ]);
    }

    /**
     * Reset / create a fresh consent link if requested.
     */
    public function regenerate(int $leadId): JsonResponse
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);
        $consent = LeadConsent::where('lead_id', $leadId)->first();

        $clientName = $lead->person?->name ?: $lead->title;
        $agentName = $lead->user?->name ?: (auth()->guard('user')->user()?->name ?: 'Agente Certificado ACA');
        $agentNpn = '19845210';
        $agencyName = 'Seguros CRM - Marketplace Agency';

        if ($consent) {
            $consent->update([
                'token' => Str::random(36),
                'status' => 'pending',
                'signature_data' => null,
                'signed_at' => null,
                'ip_address' => null,
                'user_agent' => null,
                'consent_text' => LeadConsent::getDefaultConsentText($clientName, $agentName, $agentNpn, $agencyName),
            ]);
        } else {
            return $this->get($leadId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nuevo enlace de consentimiento generado exitosamente.',
            'consent' => $consent,
            'public_url' => $consent->public_url,
            'whatsapp_message' => $consent->whatsapp_message,
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
