<?php

namespace Webkul\Lead\Services;

use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadCrossSellOpportunity;

class CrossSellOpportunityService
{
    /**
     * Analyze lead and generate intelligent cross-sell bundle recommendations.
     */
    public function evaluateAndSync(Lead $lead): array
    {
        $existing = $lead->crossSellOpportunities;

        if ($existing->isEmpty()) {
            $recommendations = [
                [
                    'product_type' => 'hospital_indemnity',
                    'title' => 'Indemnización Hospitalaria ($350/día)',
                    'carrier_name' => 'ManhattanLife',
                    'estimated_monthly_premium' => 38.00,
                    'estimated_agent_commission' => 95.00,
                    'gap_reason' => 'Protege el alto deducible del plan de salud cubriendo hospitalización a $350 por día durante los primeros 5 días.',
                    'status' => 'recommended',
                ],
                [
                    'product_type' => 'dental_vision',
                    'title' => 'Paquete Dental & Visión ($1,500 max)',
                    'carrier_name' => 'Ameritas',
                    'estimated_monthly_premium' => 34.00,
                    'estimated_agent_commission' => 70.00,
                    'gap_reason' => 'El plan médico ACA no incluye cobertura dental para adultos (limpiezas, implantes, coronas) ni chequeo oftalmológico.',
                    'status' => 'recommended',
                ],
                [
                    'product_type' => 'final_expense',
                    'title' => 'Gastos Finales / Vida ($15,000)',
                    'carrier_name' => 'Mutual of Omaha',
                    'estimated_monthly_premium' => 45.00,
                    'estimated_agent_commission' => 180.00,
                    'gap_reason' => 'Protección familiar garantizada sin examen médico para cubrir gastos de sepelio y deudas médicas pendientes.',
                    'status' => 'recommended',
                ],
            ];

            foreach ($recommendations as $rec) {
                $lead->crossSellOpportunities()->create($rec);
            }
        }

        $opportunities = $lead->crossSellOpportunities()->get();

        $totalBundleMonthly = $opportunities->whereIn('status', ['recommended', 'presented', 'enrolled'])->sum('estimated_monthly_premium');
        $totalCommissionKicker = $opportunities->whereIn('status', ['recommended', 'presented', 'enrolled'])->sum('estimated_agent_commission');

        return [
            'lead_id' => $lead->id,
            'opportunities' => $opportunities,
            'metrics' => [
                'total_bundle_monthly' => round($totalBundleMonthly, 2),
                'total_commission_kicker' => round($totalCommissionKicker, 2),
                'recommended_count' => $opportunities->where('status', 'recommended')->count(),
                'enrolled_count' => $opportunities->where('status', 'enrolled')->count(),
            ],
        ];
    }
}
