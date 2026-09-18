<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Webkul\Lead\Models\Lead;

class ClientSnapshotService
{
    public function __construct(
        protected LeadAiScoringService $aiScoringService
    ) {}

    /**
     * Generate 360-degree executive snapshot for quick agent briefing.
     */
    public function generateSnapshot(Lead $lead): array
    {
        $lead->loadMissing([
            'person',
            'user',
            'householdMembers',
            'rxMedications',
            'doctorNetworks',
            'consent',
            'dmiDocuments',
            'sepQualification',
            'quotes',
        ]);

        $person = $lead->person;
        $household = $lead->householdMembers;
        $medications = $lead->rxMedications;
        $doctors = $lead->doctorNetworks;
        $primaryDoctor = $doctors->firstWhere('is_primary_physician', true) ?: $doctors->first();
        $consent = $lead->consent;
        $urgentDmi = $lead->dmiDocuments?->first(fn ($d) => $d->urgency_level === 'critical');

        $aiInsights = $this->aiScoringService->evaluateLead($lead);
        $nextAction = ! empty($aiInsights['next_best_actions']) ? $aiInsights['next_best_actions'][0]['title'] : 'Seguimiento de rutina';

        // Age calculation
        $ageText = 'Edad no especificada';
        if ($person?->date_of_birth) {
            $ageText = Carbon::parse($person->date_of_birth)->age . ' años';
        }

        // Meds summary
        $medsSummary = $medications->isNotEmpty()
            ? $medications->pluck('medication_name')->implode(', ')
            : 'Sin medicamentos de uso continuo reportados';

        $pcpSummary = $primaryDoctor
            ? "{$primaryDoctor->doctor_name} ({$primaryDoctor->specialty})"
            : 'Pendiente por seleccionar';

        $complianceStatus = ($consent && $consent->status === 'signed')
            ? 'Consentimiento CMS firmado legalmente'
            : 'Requiere firma de Consentimiento CMS';

        if ($urgentDmi) {
            $complianceStatus .= " • ⚠️ DMI Crítico ({$urgentDmi->days_remaining}d restantes)";
        }

        // Narrative Brief
        $narrative = sprintf(
            'El beneficiario %s (%s) reside en %s. Cuenta con un censo de %d personas en su hogar. ' .
            'Médico primario: %s. Medicamentos activos: %s. Estatus de cumplimiento: %s. ' .
            'Score IA: %d/100 (%s). Acción prioritaria recomendada: %s.',
            $person?->name ?: $lead->title,
            $ageText,
            $person?->address ?: 'código postal asignado',
            $household->count() + 1,
            $pcpSummary,
            $medsSummary,
            $complianceStatus,
            $aiInsights['total_score'],
            $aiInsights['tier']['label'],
            $nextAction
        );

        return [
            'lead_id' => $lead->id,
            'client_name' => $person?->name ?: $lead->title,
            'age_text' => $ageText,
            'household_size' => $household->count() + 1,
            'primary_doctor' => $pcpSummary,
            'medications_summary' => $medsSummary,
            'compliance_status' => $complianceStatus,
            'ai_score' => $aiInsights['total_score'],
            'ai_tier' => $aiInsights['tier'],
            'next_action' => $nextAction,
            'executive_narrative' => $narrative,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
