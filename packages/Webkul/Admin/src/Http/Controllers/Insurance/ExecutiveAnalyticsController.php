<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Services\PolicyRetentionService;
use Webkul\User\Models\User;

class ExecutiveAnalyticsController extends Controller
{
    public function __construct(
        protected PolicyRetentionService $retentionService
    ) {}

    /**
     * Display the Executive Analytics & Book Valuation Dashboard.
     */
    public function index()
    {
        $metrics = $this->calculateExecutiveMetrics();

        return view('admin::insurance.analytics.index', compact('metrics'));
    }

    /**
     * Get JSON data for charts and real-time dashboard refresh.
     */
    public function data(Request $request): JsonResponse
    {
        $metrics = $this->calculateExecutiveMetrics();

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Export Executive Valuation & Portfolio Report as CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $metrics = $this->calculateExecutiveMetrics();
        $fileName = 'Valuacion_Cartera_Ejecutiva_' . now()->format('Y_m_d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($metrics) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // 1. Valuation Summary
            fputcsv($output, ['INFORME EJECUTIVO DE VALUACION DE CARTERA (BOOK OF BUSINESS)']);
            fputcsv($output, ['Generado el', now()->format('Y-m-d H:i:s')]);
            fputcsv($output, []);

            fputcsv($output, ['METRICA FINANCIERA', 'VALOR ESTIMADO']);
            fputcsv($output, ['Polizas Activas en Vigor', $metrics['valuation']['active_policies']]);
            fputcsv($output, ['Vidas Aseguradas Cubiertas', $metrics['valuation']['covered_lives']]);
            fputcsv($output, ['Prima Bruta Mensual', '$' . number_format($metrics['valuation']['monthly_gross_premium'], 2)]);
            fputcsv($output, ['Prima Bruta Anualizada', '$' . number_format($metrics['valuation']['annualized_gross_premium'], 2)]);
            fputcsv($output, ['Ingreso Mensual Recurrente Estimado (ARR / 12)', '$' . number_format($metrics['valuation']['monthly_recurring_revenue'], 2)]);
            fputcsv($output, ['Ingreso Anual Recurrente (ARR)', '$' . number_format($metrics['valuation']['annual_recurring_revenue'], 2)]);
            fputcsv($output, ['Tasa de Persistencia (Retencion)', $metrics['valuation']['persistency_rate'] . '%']);
            fputcsv($output, ['Valuacion Conservadora (1.5x ARR)', '$' . number_format($metrics['valuation']['valuation_conservative'], 2)]);
            fputcsv($output, ['Valuacion Mercado Estandar (2.0x ARR)', '$' . number_format($metrics['valuation']['valuation_standard'], 2)]);
            fputcsv($output, ['Valuacion Alta Retencion (2.5x ARR)', '$' . number_format($metrics['valuation']['valuation_aggressive'], 2)]);
            fputcsv($output, []);

            // 2. Carrier Share
            fputcsv($output, ['DISTRIBUCION POR CARRIER / COMPANIA ASEGURADORA']);
            fputcsv($output, ['Carrier', 'Polizas Activas', 'Vidas Cubiertas', 'Prima Mensual ($)', 'Participacion (%)']);
            foreach ($metrics['carrier_distribution'] as $c) {
                fputcsv($output, [
                    $c['carrier'],
                    $c['policies'],
                    $c['lives'],
                    number_format($c['monthly_premium'], 2),
                    $c['share_pct'] . '%',
                ]);
            }
            fputcsv($output, []);

            // 3. Producer Leaderboard
            fputcsv($output, ['RANKING DE PRODUCTORES / AGENTES']);
            fputcsv($output, ['Agente', 'Polizas Activas', 'Vidas Cubiertas', 'Prima Anualizada ($)', 'Tasa Persistencia (%)']);
            foreach ($metrics['leaderboard'] as $p) {
                fputcsv($output, [
                    $p['agent_name'],
                    $p['active_policies'],
                    $p['covered_lives'],
                    number_format($p['annualized_premium'], 2),
                    $p['persistency_rate'] . '%',
                ]);
            }

            fclose($output);
        }, 200, $headers);
    }

    /**
     * Compute comprehensive financial, valuation, and executive KPIs.
     */
    protected function calculateExecutiveMetrics(): array
    {
        $baseRetention = $this->retentionService->getRetentionMetrics();

        $activePolicies = InsurancePolicy::where('status', 'active')->get();
        $allPolicies = InsurancePolicy::all();

        $totalActive = $activePolicies->count();
        $coveredLives = $activePolicies->sum('members_count');
        $monthlyGross = (float) $activePolicies->sum('gross_premium');
        $annualizedGross = $monthlyGross * 12;

        // Estimated recurring commissions: $25 PMPM standard industry benchmark per active covered life
        $monthlyRevenue = $coveredLives * 25.00;
        $annualRecurringRevenue = $monthlyRevenue * 12;

        // Multiples: 1.5x (Conservative), 2.0x (Standard), 2.5x (High-growth / >85% persistency)
        $valuationConservative = $annualRecurringRevenue * 1.5;
        $valuationStandard = $annualRecurringRevenue * 2.0;
        $valuationAggressive = $annualRecurringRevenue * 2.5;

        // 1. Carrier Distribution
        $carrierDistribution = [];
        $carrierGroups = $allPolicies->groupBy('carrier_name');
        foreach ($carrierGroups as $carrier => $group) {
            $activeGroup = $group->where('status', 'active');
            $share = $totalActive > 0 ? round(($activeGroup->count() / $totalActive) * 100, 1) : 0;

            $carrierDistribution[] = [
                'carrier' => $carrier ?: 'Carrier No Especificado',
                'policies' => $activeGroup->count(),
                'lives' => $activeGroup->sum('members_count'),
                'monthly_premium' => round($activeGroup->sum('gross_premium'), 2),
                'share_pct' => $share,
            ];
        }
        usort($carrierDistribution, fn ($a, $b) => $b['policies'] <=> $a['policies']);

        // 2. Metal Tier Distribution
        $metalTiers = [
            'bronze' => ['label' => 'Bronze', 'count' => 0, 'color' => '#cd7f32'],
            'silver' => ['label' => 'Silver (CSR)', 'count' => 0, 'color' => '#94a3b8'],
            'gold' => ['label' => 'Gold', 'count' => 0, 'color' => '#eab308'],
            'platinum' => ['label' => 'Platinum', 'count' => 0, 'color' => '#6366f1'],
        ];

        foreach ($activePolicies as $p) {
            $tier = strtolower($p->metal_tier ?? 'silver');
            if (isset($metalTiers[$tier])) {
                $metalTiers[$tier]['count']++;
            } else {
                $metalTiers['silver']['count']++;
            }
        }

        // 3. Network Type Distribution
        $networkTypes = [
            'HMO' => $activePolicies->filter(fn ($p) => stripos($p->network_type, 'HMO') !== false)->count(),
            'EPO' => $activePolicies->filter(fn ($p) => stripos($p->network_type, 'EPO') !== false)->count(),
            'PPO' => $activePolicies->filter(fn ($p) => stripos($p->network_type, 'PPO') !== false)->count(),
        ];

        // 4. Producer Leaderboard
        $users = User::all();
        $leaderboard = [];

        foreach ($users as $user) {
            $userPolicies = $allPolicies->where('user_id', $user->id);
            if ($userPolicies->isEmpty()) {
                continue;
            }

            $userActive = $userPolicies->where('status', 'active');
            $userLives = $userActive->sum('members_count');
            $userGrossAnnual = $userActive->sum('gross_premium') * 12;
            $persistency = $userPolicies->count() > 0 ? round(($userActive->count() / $userPolicies->count()) * 100, 1) : 100.0;

            $leaderboard[] = [
                'user_id' => $user->id,
                'agent_name' => $user->name,
                'active_policies' => $userActive->count(),
                'covered_lives' => $userLives,
                'annualized_premium' => round($userGrossAnnual, 2),
                'persistency_rate' => $persistency,
            ];
        }
        usort($leaderboard, fn ($a, $b) => $b['active_policies'] <=> $a['active_policies']);

        // 5. OEP Season Progress (Nov 1 to Jan 15 target: default 200 policies)
        $oepTarget = 200;
        $oepProgressPct = min(100, round(($totalActive / max(1, $oepTarget)) * 100, 1));

        return [
            'valuation' => [
                'active_policies' => $totalActive,
                'covered_lives' => $coveredLives,
                'monthly_gross_premium' => round($monthlyGross, 2),
                'annualized_gross_premium' => round($annualizedGross, 2),
                'monthly_recurring_revenue' => round($monthlyRevenue, 2),
                'annual_recurring_revenue' => round($annualRecurringRevenue, 2),
                'persistency_rate' => $baseRetention['persistency_rate'] ?? 95.0,
                'valuation_conservative' => round($valuationConservative, 2),
                'valuation_standard' => round($valuationStandard, 2),
                'valuation_aggressive' => round($valuationAggressive, 2),
            ],
            'retention' => $baseRetention,
            'carrier_distribution' => $carrierDistribution,
            'metal_tiers' => array_values($metalTiers),
            'network_types' => $networkTypes,
            'leaderboard' => $leaderboard,
            'oep' => [
                'target' => $oepTarget,
                'enrolled' => $totalActive,
                'progress_pct' => $oepProgressPct,
            ],
        ];
    }
}
