<?php

namespace Webkul\Admin\Http\Controllers\Lead;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadSepQualification;

class EnrollmentPeriodController extends Controller
{
    /**
     * Get the SEP / QLE qualification and federal OEP status for a lead.
     */
    public function get(int $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);
        $qualification = LeadSepQualification::where('lead_id', $leadId)->first();
        $federalStatus = LeadSepQualification::getCurrentFederalEnrollmentStatus();
        $eventDefinitions = LeadSepQualification::getEventDefinitions();

        return response()->json([
            'success' => true,
            'lead_id' => $leadId,
            'federal_status' => $federalStatus,
            'qualification' => $qualification,
            'event_definitions' => $eventDefinitions,
        ]);
    }

    /**
     * Save or update the Qualifying Life Event (QLE) and calculate the 60-day SEP window.
     */
    public function save(Request $request, int $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $validated = $request->validate([
            'event_type' => 'required|string',
            'event_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $eventType = $validated['event_type'];
        $eventDate = Carbon::parse($validated['event_date']);

        $definitions = LeadSepQualification::getEventDefinitions();
        if (! isset($definitions[$eventType])) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de evento de vida no reconocido por CMS.',
            ], 422);
        }

        // Calculate 60-day window deadline
        $sepDeadline = $eventDate->copy()->addDays(60);
        $today = Carbon::today();
        $isEligible = $today->lessThanOrEqualTo($sepDeadline);

        // Calculate effective date
        if ($eventType === 'birth_adoption') {
            $effectiveDate = $eventDate->copy();
        } else {
            $effectiveDate = $today->copy()->addMonth()->startOfMonth();
        }

        $requiredDocs = $definitions[$eventType]['documents'];

        $user = auth()->guard('user')->user() ?: $lead->user;

        $qualification = LeadSepQualification::updateOrCreate(
            ['lead_id' => $leadId],
            [
                'user_id' => $user?->id,
                'event_type' => $eventType,
                'event_date' => $eventDate->toDateString(),
                'sep_deadline' => $sepDeadline->toDateString(),
                'effective_date' => $effectiveDate->toDateString(),
                'is_eligible' => $isEligible,
                'required_documents' => $requiredDocs,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        $days = $qualification->days_remaining;
        $statusMsg = $isEligible
            ? "¡Evento Calificante validado! El cliente cuenta con {$days} días restantes para completar su inscripción."
            : 'Atención: La fecha del evento supera la ventana legal de 60 días permitida por CMS.';

        return response()->json([
            'success' => true,
            'message' => $statusMsg,
            'qualification' => $qualification,
        ]);
    }

    /**
     * Check or uncheck a verified document in the checklist.
     */
    public function toggleDocument(Request $request, int $leadId): JsonResponse
    {
        $qualification = LeadSepQualification::where('lead_id', $leadId)->firstOrFail();

        $validated = $request->validate([
            'document' => 'required|string',
            'verified' => 'required|boolean',
        ]);

        $verifiedDocs = $qualification->verified_documents ?: [];
        $doc = $validated['document'];

        if ($validated['verified']) {
            if (! in_array($doc, $verifiedDocs)) {
                $verifiedDocs[] = $doc;
            }
        } else {
            $verifiedDocs = array_values(array_filter($verifiedDocs, fn ($item) => $item !== $doc));
        }

        $allVerified = count($verifiedDocs) >= count($qualification->required_documents ?? []);

        $qualification->update([
            'verified_documents' => $verifiedDocs,
            'verified_at' => $allVerified ? Carbon::now() : null,
        ]);

        return response()->json([
            'success' => true,
            'qualification' => $qualification,
            'all_verified' => $allVerified,
        ]);
    }
}
