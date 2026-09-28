<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\LeadDmiDocument;
use Webkul\Lead\Models\PolicyServiceCase;

class InsuredPortalController extends Controller
{
    use PDFHandler;

    /**
     * Display public digital member portal & insurance card.
     */
    public function show(string $token): View
    {
        $policy = InsurancePolicy::with([
            'lead.householdMembers',
            'lead.person',
            'user',
            'quote',
            'person',
            'serviceCases' => fn ($q) => $q->where('is_shared_with_client', true)->orderBy('created_at', 'desc'),
        ])
            ->where('portal_token', $token)
            ->firstOrFail();

        $support = $policy->getCarrierSupportContacts();
        $coveredMembers = $policy->lead?->householdMembers ?: collect();
        $agent = $policy->user ?: $policy->lead?->user;
        $sharedCases = $policy->serviceCases;

        return view('admin::insurance.portal.index', [
            'policy' => $policy,
            'support' => $support,
            'coveredMembers' => $coveredMembers,
            'agent' => $agent,
            'token' => $token,
            'sharedCases' => $sharedCases,
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

    /**
     * Submit a 1095-A tax form request or post-sale service request from client portal.
     */
    public function requestTaxDocument(Request $request, string $token): JsonResponse
    {
        $policy = InsurancePolicy::where('portal_token', $token)->firstOrFail();

        $request->validate([
            'tax_year' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $taxYear = (int) $request->input('tax_year', date('Y') - 1);

        $serviceCase = PolicyServiceCase::create([
            'policy_id' => $policy->id,
            'lead_id' => $policy->lead_id,
            'person_id' => $policy->person_id ?: $policy->lead?->person_id,
            'user_id' => $policy->user_id ?: $policy->lead?->user_id,
            'category' => 'tax_1095a',
            'priority' => 'normal',
            'status' => 'open',
            'subject' => "Solicitud de Declaración 1095-A (Año Fiscal {$taxYear})",
            'description' => $request->input('notes') ?: "El asegurado solicitó su formulario fiscal 1095-A para la declaración de renta correspondiente al año fiscal {$taxYear}.",
            'due_date' => now()->addDays(5),
            'is_shared_with_client' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Solicitud de Formulario 1095-A enviada con éxito! Su agente tramitará el documento con el Marketplace y se lo compartirá aquí.',
            'ticket_number' => $serviceCase->ticket_number,
            'data' => $serviceCase,
        ]);
    }

    /**
     * Download shared document (e.g. 1095-A) from member portal.
     */
    public function downloadSharedDocument(string $token, int $caseId)
    {
        $policy = InsurancePolicy::where('portal_token', $token)->firstOrFail();

        $serviceCase = PolicyServiceCase::where('policy_id', $policy->id)
            ->findOrFail($caseId);

        if (! $serviceCase->is_shared_with_client) {
            abort(403, 'Acceso denegado: este documento no ha sido compartido con el cliente.');
        }

        if (! $serviceCase->attachment_path || ! Storage::disk('public')->exists($serviceCase->attachment_path)) {
            abort(404, 'El documento aún no ha sido cargado por su agente.');
        }

        return Storage::disk('public')->download($serviceCase->attachment_path);
    }
}
