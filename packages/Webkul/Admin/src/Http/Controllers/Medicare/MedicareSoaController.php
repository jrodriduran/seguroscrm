<?php

namespace Webkul\Admin\Http\Controllers\Medicare;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadMedicareSoa;

class MedicareSoaController extends Controller
{
    use PDFHandler;

    /**
     * Get or initialize the Medicare Scope of Appointment (SOA) for a lead.
     */
    public function get(int $leadId): JsonResponse
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);

        $soa = LeadMedicareSoa::where('lead_id', $lead->id)->first();

        if (! $soa) {
            $beneficiaryName = $lead->person?->name ?: $lead->title;
            $beneficiaryPhone = collect($lead->person?->contact_numbers ?? [])->first()['value'] ?? null;
            $agentName = $lead->user?->name ?: (auth()->guard('user')->user()?->name ?: 'Agente Certificado Medicare');
            $agentNpn = '19845210';
            $agencyName = 'Seguros CRM - Medicare Division';

            $soa = LeadMedicareSoa::create([
                'lead_id' => $lead->id,
                'person_id' => $lead->person_id,
                'user_id' => $lead->user_id ?: auth()->guard('user')->id(),
                'token' => Str::random(36),
                'status' => 'pending',
                'beneficiary_name' => $beneficiaryName,
                'beneficiary_phone' => $beneficiaryPhone,
                'agent_name' => $agentName,
                'agent_npn' => $agentNpn,
                'agency_name' => $agencyName,
                'discuss_medicare_advantage' => true,
                'discuss_prescription_drug' => true,
                'discuss_medigap' => false,
                'discuss_dental_vision' => false,
                'discuss_hospital_indemnity' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'soa' => $soa,
            'public_url' => $soa->public_url,
            'whatsapp_message' => $soa->whatsapp_message,
            'is_eligible' => $soa->is_eligible_for_appointment,
            'hours_remaining' => $soa->hours_remaining_until_eligible,
        ]);
    }

    /**
     * Get WhatsApp invitation link and payload.
     */
    public function getWhatsAppLink(int $leadId): JsonResponse
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);
        $soa = LeadMedicareSoa::where('lead_id', $lead->id)->first();

        if (! $soa) {
            $res = $this->get($leadId);
            $data = $res->getData(true);
            $soa = LeadMedicareSoa::find($data['soa']['id']);
        }

        $phone = $soa->beneficiary_phone ?: collect($lead->person?->contact_numbers ?? [])->first()['value'] ?? '';
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);

        $whatsappUrl = 'https://api.whatsapp.com/send?phone='.$cleanPhone.'&text='.urlencode($soa->whatsapp_message);

        return response()->json([
            'success' => true,
            'phone' => $phone,
            'clean_phone' => $cleanPhone,
            'message' => $soa->whatsapp_message,
            'public_url' => $soa->public_url,
            'whatsapp_url' => $whatsappUrl,
        ]);
    }

    /**
     * Apply allowed CMS Exception to the 48-hour rule.
     */
    public function applyException(Request $request, int $leadId): JsonResponse
    {
        $request->validate([
            'exception_reason' => 'required|string|in:walk_in,end_of_enrollment',
            'notes' => 'required|string|min:5',
        ]);

        $soa = LeadMedicareSoa::where('lead_id', $leadId)->firstOrFail();

        $soa->update([
            'exception_reason' => $request->input('exception_reason'),
            'notes' => ($soa->notes ? $soa->notes."\n" : '')
                .'[CMS EXCEPCIÓN 48h - '.now()->format('Y-m-d H:i').']: '
                .$request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Excepción CMS aplicada y registrada en auditoría.',
            'soa' => $soa->fresh(),
        ]);
    }

    /**
     * Regenerate fresh SOA link.
     */
    public function regenerate(int $leadId): JsonResponse
    {
        $soa = LeadMedicareSoa::where('lead_id', $leadId)->first();

        if ($soa) {
            $soa->update([
                'token' => Str::random(36),
                'status' => 'pending',
                'signature_data' => null,
                'signed_at' => null,
                'appointment_eligible_at' => null,
                'exception_reason' => 'none',
                'ip_address' => null,
                'user_agent' => null,
            ]);
        } else {
            return $this->get($leadId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nuevo enlace SOA generado exitosamente.',
            'soa' => $soa,
            'public_url' => $soa->public_url,
            'whatsapp_message' => $soa->whatsapp_message,
        ]);
    }

    /**
     * Print View.
     */
    public function printCertificate(int $leadId)
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);
        $soa = LeadMedicareSoa::where('lead_id', $lead->id)->firstOrFail();

        return view('admin::medicare.soa.certificate', [
            'lead' => $lead,
            'soa' => $soa,
            'isPdf' => false,
        ]);
    }

    /**
     * Direct PDF Download.
     */
    public function downloadCertificatePdf(int $leadId)
    {
        $lead = Lead::with(['person', 'user'])->findOrFail($leadId);
        $soa = LeadMedicareSoa::where('lead_id', $lead->id)->firstOrFail();

        $html = view('admin::medicare.soa.certificate', [
            'lead' => $lead,
            'soa' => $soa,
            'isPdf' => true,
        ])->render();

        $beneficiarySlug = Str::slug($soa->beneficiary_name ?: 'beneficiario');
        $fileName = 'Medicare_SOA_CMS_'.$lead->id.'_'.$beneficiarySlug;

        return $this->downloadPDF($html, $fileName);
    }
}
