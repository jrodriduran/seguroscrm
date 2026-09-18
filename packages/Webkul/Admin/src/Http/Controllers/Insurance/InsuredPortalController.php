<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\LeadDmiDocument;

class InsuredPortalController extends Controller
{
    use PDFHandler;

    /**
     * Display public digital member portal & insurance card.
     */
    public function show(string $token): View
    {
        $policy = InsurancePolicy::with(['lead.householdMembers', 'lead.person', 'user', 'quote', 'person'])
            ->where('portal_token', $token)
            ->firstOrFail();

        $support = $policy->getCarrierSupportContacts();
        $coveredMembers = $policy->lead?->householdMembers ?: collect();
        $agent = $policy->user ?: $policy->lead?->user;

        return view('admin::insurance.portal.index', [
            'policy' => $policy,
            'support' => $support,
            'coveredMembers' => $coveredMembers,
            'agent' => $agent,
            'token' => $token,
        ]);
    }

    /**
     * Download printable Digital Insurance ID Card PDF.
     */
    public function downloadCard(string $token)
    {
        $policy = InsurancePolicy::with(['lead.person', 'user', 'quote'])
            ->where('portal_token', $token)
            ->firstOrFail();

        $support = $policy->getCarrierSupportContacts();
        $agent = $policy->user ?: $policy->lead?->user;

        $html = view('admin::insurance.portal.id_card_pdf', [
            'policy' => $policy,
            'support' => $support,
            'agent' => $agent,
            'isPdf' => true,
        ])->render();

        $fileName = 'Tarjeta_Seguro_'.$policy->policy_number.'_'.Str::slug($policy->carrier_name);

        return $this->downloadPDF($html, $fileName);
    }

    /**
     * Allow policyholder to upload a verification document (income/DMI/ID) from mobile.
     */
    public function uploadDocument(Request $request, string $token): JsonResponse
    {
        $policy = InsurancePolicy::where('portal_token', $token)->firstOrFail();

        $validated = $request->validate([
            'doc_type' => 'required|string|max:80',
            'document' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        if (! $policy->lead_id) {
            return response()->json([
                'success' => false,
                'message' => 'No hay expediente asociado a esta póliza.',
            ], 422);
        }

        $path = $request->file('document')->store('dmi_documents/'.$policy->lead_id, 'public');

        $dmi = LeadDmiDocument::create([
            'lead_id' => $policy->lead_id,
            'doc_type' => $validated['doc_type'],
            'title' => 'Documento enviado por el asegurado desde el portal: '.$request->file('document')->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'submitted_to_marketplace',
            'submitted_at' => now(),
            'notes' => 'Cargado vía autoservicio por el titular el '.now()->format('d/m/Y H:i'),
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Documento recibido con éxito! Su agente revisará la documentación para validarla ante el Mercado.',
            'document' => $dmi,
        ]);
    }
}
