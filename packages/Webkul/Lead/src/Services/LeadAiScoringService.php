<?php

namespace Webkul\Lead\Services;

use Carbon\Carbon;
use Webkul\Lead\Models\Lead;

class LeadAiScoringService
{
    /**
     * Compute multidimensional AI Lead Score (0 - 100) and Next Best Actions.
     */
    public function evaluateLead(Lead $lead): array
    {
        $lead->loadMissing(['person', 'consent', 'dmiDocuments', 'sepQualification', 'rxMedications', 'doctorNetworks', 'householdMembers']);

        $scoreBreakdown = [
            'sep_oep_urgency' => 0,
            'medicare_turning_65' => 0,
            'subsidy_potential' => 0,
            'compliance_readiness' => 0,
            'retention_dmi_urgency' => 0,
        ];

        $nextBestActions = [];

        // 1. Evaluate SEP / OEP Urgency Factor (Max 30 pts)
        $sep = $lead->sepQualification;
        $now = Carbon::now();

        // Check if national OEP is active (Nov 1 to Jan 15)
        $month = (int) $now->format('n');
        $isOepActive = ($month === 11 || $month === 12 || ($month === 1 && (int) $now->format('j') <= 15));

        if ($sep && $sep->status === 'qualified') {
            $daysRemaining = $sep->days_remaining;
            if ($daysRemaining > 0 && $daysRemaining <= 7) {
                $scoreBreakdown['sep_oep_urgency'] = 30;
                $nextBestActions[] = [
                    'priority' => 'urgent',
                    'icon' => '🚨',
                    'title' => "Ventana SEP por vencer en {$daysRemaining} días",
                    'description' => 'El período especial de inscripción expira inminentemente. Cierra la aplicación hoy para no perder el derecho a cobertura federal.',
                    'action_label' => 'Revisar SEP',
                    'tab' => 'enrollment_period',
                ];
            } elseif ($daysRemaining > 7 && $daysRemaining <= 30) {
                $scoreBreakdown['sep_oep_urgency'] = 20;
                $nextBestActions[] = [
                    'priority' => 'high',
                    'icon' => '⏳',
                    'title' => "Ventana SEP activa ({$daysRemaining} días restantes)",
                    'description' => 'Verifica la documentación del evento de vida calificado (QLE) y presenta las opciones de planes.',
                    'action_label' => 'Ver Documentos SEP',
                    'tab' => 'enrollment_period',
                ];
            }
        } elseif ($isOepActive) {
            $scoreBreakdown['sep_oep_urgency'] = 20;
            $nextBestActions[] = [
                'priority' => 'high',
                'icon' => '🗓️',
                'title' => 'Inscripción Abierta (OEP) en Curso',
                'description' => 'Temporada alta federal. Presenta la propuesta de planes de salud y selecciona plan antes del 15 de enero.',
                'action_label' => 'Ver Cotizaciones',
                'tab' => 'quotes',
            ];
        } else {
            // Outside OEP and no SEP qualified
            $nextBestActions[] = [
                'priority' => 'medium',
                'icon' => '🔍',
                'title' => 'Calificar Evento de Vida (SEP)',
                'description' => 'Para inscribir fuera de OEP, valida si el cliente tuvo pérdida de cobertura, mudanza, cambio de ingresos o matrimonio.',
                'action_label' => 'Validar SEP',
                'tab' => 'enrollment_period',
            ];
        }

        // 2. Evaluate Medicare Aging-In (Turning 65 within 6 months) (Max 25 pts)
        $isTurning65 = false;
        $birthDate = null;

        // Check lead's person or household members
        if ($lead->person && ! empty($lead->person->date_of_birth)) {
            $birthDate = Carbon::parse($lead->person->date_of_birth);
        } elseif ($lead->householdMembers && $lead->householdMembers->isNotEmpty()) {
            $applicant = $lead->householdMembers->firstWhere('relationship', 'primary_subscriber') ?: $lead->householdMembers->first();
            if ($applicant && ! empty($applicant->date_of_birth)) {
                $birthDate = Carbon::parse($applicant->date_of_birth);
            }
        }

        if ($birthDate) {
            $ageInMonths = $birthDate->diffInMonths($now);
            $ageInYears = $birthDate->age;

            // Age between 64 years 6 months and 65 years 3 months (Medicare IEP Window)
            if ($ageInMonths >= (64 * 12 + 6) && $ageInMonths <= (65 * 12 + 3)) {
                $isTurning65 = true;
                $scoreBreakdown['medicare_turning_65'] = 25;
                $nextBestActions[] = [
                    'priority' => 'high',
                    'icon' => '🎂',
                    'title' => 'Oportunidad Medicare: Transición a 65 Años',
                    'description' => 'El cliente está en su Ventana de Inscripción Inicial (IEP). Califica para Medicare Advantage (Parte C) y Parte D.',
                    'action_label' => 'Crear Medicare SOA',
                    'tab' => 'medicare_soa',
                ];
            }
        }

        // 3. APTC Subsidy Potential (Silver CSR 94% / 87%) (Max 20 pts)
        $householdSize = $lead->householdMembers?->count() ?: 1;
        // Check if household has members or estimated subsidy potential
        if ($householdSize >= 2) {
            $scoreBreakdown['subsidy_potential'] = 20; // High subsidy probability for families
        } else {
            $scoreBreakdown['subsidy_potential'] = 15;
        }

        // 4. Compliance & Readiness (Max 15 pts)
        $hasConsent = ($lead->consent && $lead->consent->status === 'signed');
        $hasSoa = ($lead->medicareSoas && $lead->medicareSoas->where('status', 'signed')->isNotEmpty());

        if ($hasConsent || $hasSoa) {
            $scoreBreakdown['compliance_readiness'] += 10;
        } else {
            $nextBestActions[] = [
                'priority' => 'urgent',
                'icon' => '📋',
                'title' => 'Falta Consentimiento Electrónico CMS',
                'description' => 'Obligatorio según 45 CFR § 155.220 antes de someter cualquier cotización o aplicación en HealthCare.gov.',
                'action_label' => 'Enviar Enlace de Consentimiento',
                'tab' => 'consent',
            ];
        }

        // Rx / Provider check
        $hasRx = ($lead->rxMedications && $lead->rxMedications->isNotEmpty());
        $hasDoctor = ($lead->doctorNetworks && $lead->doctorNetworks->isNotEmpty());

        if ($hasRx || $hasDoctor) {
            $scoreBreakdown['compliance_readiness'] += 5;
        } else {
            $nextBestActions[] = [
                'priority' => 'info',
                'icon' => '💊',
                'title' => 'Registrar Medicamentos y Doctores del Cliente',
                'description' => 'Asegura la retención verificando si sus médicos habituales y medicinas están cubiertos en la red.',
                'action_label' => 'Abrir Ficha Rx & Red',
                'tab' => 'rx_network',
            ];
        }

        // 5. Retention / DMI Deadline Risk (Max 10 pts)
        $urgentDmi = $lead->dmiDocuments?->first(fn ($doc) => $doc->status === 'pending_upload' && $doc->urgency_level === 'critical');
        if ($urgentDmi) {
            $scoreBreakdown['retention_dmi_urgency'] = 10;
            $nextBestActions[] = [
                'priority' => 'urgent',
                'icon' => '⚠️',
                'title' => "Documento DMI Crítico: {$urgentDmi->days_remaining}d restantes",
                'description' => "Riesgo de perder el subsidio APTC: Debe subir prueba de '{$urgentDmi->title}' urgentemente.",
                'action_label' => 'Subir Documento DMI',
                'tab' => 'dmi_documents',
            ];
        }

        // Total calculation
        $totalScore = min(100, array_sum($scoreBreakdown));

        $tier = match (true) {
            $totalScore >= 75 => ['label' => 'Hot Lead', 'badge' => '🔥 Prioridad Alta', 'color' => 'rose'],
            $totalScore >= 50 => ['label' => 'Warm Lead', 'badge' => '⚡ Oportunidad Activa', 'color' => 'amber'],
            default => ['label' => 'Nurture Lead', 'badge' => '❄️ Seguimiento Estándar', 'color' => 'blue'],
        };

        // Sort next best actions by priority: urgent, high, medium, info
        $priorityOrder = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'info' => 4];
        usort($nextBestActions, fn ($a, $b) => ($priorityOrder[$a['priority']] ?? 5) <=> ($priorityOrder[$b['priority']] ?? 5));

        return [
            'lead_id' => $lead->id,
            'total_score' => $totalScore,
            'tier' => $tier,
            'breakdown' => $scoreBreakdown,
            'next_best_actions' => $nextBestActions,
            'evaluated_at' => now()->toIso8601String(),
        ];
    }
}
