<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadDoctorNetwork;
use Webkul\Lead\Models\LeadRxMedication;

class RxProviderNetworkController extends Controller
{
    /**
     * Get medications and providers list with summary analytics for a lead.
     */
    public function getLeadSummary(int $leadId): JsonResponse
    {
        $lead = Lead::with(['rxMedications', 'doctorNetworks'])->findOrFail($leadId);

        $medications = $lead->rxMedications;
        $doctors = $lead->doctorNetworks;

        $totalEstimatedCopay30d = $medications->sum('estimated_copay_30d');
        $totalEstimatedCopay90d = $medications->sum('estimated_copay_90d_mail');
        $specialtyDrugsCount = $medications->filter(fn ($m) => str_contains($m->drug_tier, 'Tier 5'))->count();
        $priorAuthCount = $medications->where('requires_prior_authorization', true)->count();

        $pcp = $doctors->firstWhere('is_primary_physician', true);

        return response()->json([
            'success' => true,
            'lead_id' => $leadId,
            'client_name' => $lead->person?->name ?: $lead->title,
            'medications' => $medications,
            'doctors' => $doctors,
            'metrics' => [
                'total_drugs' => $medications->count(),
                'total_doctors' => $doctors->count(),
                'total_copay_30d' => round($totalEstimatedCopay30d, 2),
                'total_copay_90d_mail' => round($totalEstimatedCopay90d, 2),
                'specialty_drugs_count' => $specialtyDrugsCount,
                'prior_auth_count' => $priorAuthCount,
                'has_pcp' => (bool) $pcp,
                'pcp_name' => $pcp?->doctor_name,
            ],
        ]);
    }

    /**
     * Add a prescription medication to the lead's formulary checklist.
     */
    public function storeMedication(Request $request, int $leadId)
    {
        $lead = Lead::findOrFail($leadId);

        $validated = $request->validate([
            'medication_name' => 'required|string|max:150',
            'dosage' => 'nullable|string|max:80',
            'frequency' => 'nullable|string|max:80',
            'quantity_per_30_days' => 'nullable|integer|min:1',
            'drug_tier' => 'required|string|max:40',
            'requires_prior_authorization' => 'nullable|boolean',
            'requires_step_therapy' => 'nullable|boolean',
            'has_quantity_limit' => 'nullable|boolean',
            'estimated_copay_30d' => 'nullable|numeric|min:0',
            'estimated_copay_90d_mail' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $medication = $lead->rxMedications()->create([
            'medication_name' => $validated['medication_name'],
            'dosage' => $validated['dosage'] ?? 'Standard',
            'frequency' => $validated['frequency'] ?? 'Daily',
            'quantity_per_30_days' => $validated['quantity_per_30_days'] ?? 30,
            'drug_tier' => $validated['drug_tier'],
            'requires_prior_authorization' => (bool) ($validated['requires_prior_authorization'] ?? false),
            'requires_step_therapy' => (bool) ($validated['requires_step_therapy'] ?? false),
            'has_quantity_limit' => (bool) ($validated['has_quantity_limit'] ?? false),
            'estimated_copay_30d' => $validated['estimated_copay_30d'] ?? 0.00,
            'estimated_copay_90d_mail' => $validated['estimated_copay_90d_mail'] ?? 0.00,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Medicamento agregado al perfil farmacéutico.',
                'medication' => $medication,
            ]);
        }

        session()->flash('success', 'Medicamento agregado exitosamente al perfil farmacéutico.');

        return redirect()->back();
    }

    /**
     * Delete medication from lead.
     */
    public function destroyMedication(int $leadId, int $medicationId)
    {
        $medication = LeadRxMedication::where('lead_id', $leadId)->findOrFail($medicationId);
        $medication->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Medicamento eliminado del perfil farmacéutico.',
            ]);
        }

        session()->flash('success', 'Medicamento eliminado correctamente.');

        return redirect()->back();
    }

    /**
     * Add or update doctor/provider in lead's preferred medical network.
     */
    public function storeDoctor(Request $request, int $leadId)
    {
        $lead = Lead::findOrFail($leadId);

        $validated = $request->validate([
            'doctor_name' => 'required|string|max:150',
            'specialty' => 'nullable|string|max:100',
            'npi_number' => 'nullable|string|max:15',
            'clinic_or_hospital' => 'nullable|string|max:180',
            'address_city_state' => 'nullable|string|max:180',
            'phone' => 'nullable|string|max:50',
            'carrier_network_status' => 'nullable|array',
            'is_primary_physician' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        // If marked as PCP, unmark any previous PCP for this lead
        if (! empty($validated['is_primary_physician'])) {
            $lead->doctorNetworks()->update(['is_primary_physician' => false]);
        }

        $doctor = $lead->doctorNetworks()->create([
            'doctor_name' => $validated['doctor_name'],
            'specialty' => $validated['specialty'] ?? 'Primary Care Physician (PCP)',
            'npi_number' => $validated['npi_number'] ?? null,
            'clinic_or_hospital' => $validated['clinic_or_hospital'] ?? null,
            'address_city_state' => $validated['address_city_state'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'carrier_network_status' => $validated['carrier_network_status'] ?? [
                'Florida Blue' => 'In-Network',
                'Ambetter' => 'In-Network',
                'UnitedHealthcare' => 'In-Network',
                'Oscar' => 'In-Network',
            ],
            'is_primary_physician' => (bool) ($validated['is_primary_physician'] ?? false),
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Médico/Proveedor registrado en la red del cliente.',
                'doctor' => $doctor,
            ]);
        }

        session()->flash('success', 'Médico/Proveedor registrado exitosamente.');

        return redirect()->back();
    }

    /**
     * Delete doctor/provider from lead.
     */
    public function destroyDoctor(int $leadId, int $doctorId)
    {
        $doctor = LeadDoctorNetwork::where('lead_id', $leadId)->findOrFail($doctorId);
        $doctor->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Médico/Proveedor eliminado de la red del cliente.',
            ]);
        }

        session()->flash('success', 'Médico eliminado correctamente.');

        return redirect()->back();
    }

    /**
     * Generate printable Rx & Doctor Network Summary PDF.
     */
    public function downloadSummaryPdf(int $leadId)
    {
        $lead = Lead::with(['person', 'user', 'rxMedications', 'doctorNetworks'])->findOrFail($leadId);

        $html = view('admin::insurance.rx_network.summary_pdf', [
            'lead' => $lead,
            'medications' => $lead->rxMedications,
            'doctors' => $lead->doctorNetworks,
            'generatedAt' => now()->format('M d, Y - h:i A'),
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('letter', 'portrait');

        $clientSlug = Str::slug($lead->person?->name ?: $lead->title ?: 'beneficiary');
        $fileName = "Resumen_Medicinas_Doctores_{$lead->id}_{$clientSlug}.pdf";

        return $pdf->download($fileName);
    }
}
