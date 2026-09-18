<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Models\CarrierStatement;
use Webkul\Lead\Models\CarrierStatementItem;
use Webkul\Lead\Models\InsuranceCommission;
use Webkul\Lead\Models\InsuranceCommissionRate;
use Webkul\Lead\Services\CommissionReconciliationService;
use Webkul\User\Models\User;

class CommissionController extends Controller
{
    /**
     * Display commissions dashboard & ledger.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = InsuranceCommission::with(['lead.person', 'user', 'quote']);

        // Filters
        if ($request->filled('carrier')) {
            $query->where('carrier_name', $request->input('carrier'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $commissions = $query->latest()->get();

        // Calculate KPI summaries
        $activeCommissions = $commissions->where('status', 'active');
        $totalGrossMonthly = $activeCommissions->sum('gross_monthly');
        $totalAgentMonthly = $activeCommissions->sum('agent_monthly');
        $totalAgencyMonthly = $activeCommissions->sum('agency_monthly');
        $activePoliciesCount = $activeCommissions->count();

        // Carrier breakdown
        $carrierStats = $activeCommissions->groupBy('carrier_name')->map(function ($items, $carrier) {
            return [
                'carrier' => $carrier,
                'count' => $items->count(),
                'gross' => $items->sum('gross_monthly'),
                'agent_net' => $items->sum('agent_monthly'),
                'agency_net' => $items->sum('agency_monthly'),
            ];
        })->values();

        $rates = InsuranceCommissionRate::all();
        $agents = User::select('id', 'name', 'email')->get();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'commissions' => $commissions,
                'kpis' => [
                    'total_gross_monthly' => $totalGrossMonthly,
                    'total_agent_monthly' => $totalAgentMonthly,
                    'total_agency_monthly' => $totalAgencyMonthly,
                    'active_policies' => $activePoliciesCount,
                ],
                'carrier_stats' => $carrierStats,
            ]);
        }

        return view('admin::insurance.commissions.index', compact(
            'commissions',
            'rates',
            'agents',
            'totalGrossMonthly',
            'totalAgentMonthly',
            'totalAgencyMonthly',
            'activePoliciesCount',
            'carrierStats'
        ));
    }

    /**
     * Manually record an insurance policy commission.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'carrier_name' => 'required|string|max:100',
            'user_id' => 'required|integer|exists:users,id',
            'lead_id' => 'nullable|integer|exists:leads,id',
            'policy_number' => 'nullable|string|max:100',
            'plan_name' => 'nullable|string|max:150',
            'metal_tier' => 'nullable|string|max:50',
            'members_count' => 'required|integer|min:1',
            'rate_per_member' => 'required|numeric|min:0',
            'agent_split_percentage' => 'required|numeric|min:0|max:100',
            'effective_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $members = (int) $validated['members_count'];
        $rate = (float) $validated['rate_per_member'];
        $splitPct = (float) $validated['agent_split_percentage'];

        $gross = round($rate * $members, 2);
        $agentMonthly = round($gross * ($splitPct / 100), 2);
        $agencyMonthly = round($gross - $agentMonthly, 2);

        $commission = InsuranceCommission::create([
            'lead_id' => $validated['lead_id'] ?? null,
            'user_id' => $validated['user_id'],
            'carrier_name' => $validated['carrier_name'],
            'policy_number' => $validated['policy_number'] ?? null,
            'plan_name' => $validated['plan_name'] ?? null,
            'metal_tier' => $validated['metal_tier'] ?? 'Silver',
            'members_count' => $members,
            'commission_type' => 'pmpm',
            'rate_per_member' => $rate,
            'gross_monthly' => $gross,
            'agent_split_percentage' => $splitPct,
            'agent_monthly' => $agentMonthly,
            'agency_monthly' => $agencyMonthly,
            'status' => 'active',
            'effective_date' => $validated['effective_date'] ?? now()->startOfMonth(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Póliza y comisión registrada exitosamente.',
            'commission' => $commission->load(['lead.person', 'user']),
        ]);
    }

    /**
     * Update commission status, split percentage, or policy details.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $commission = InsuranceCommission::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|string|in:active,pending,paid,cancelled',
            'policy_number' => 'sometimes|nullable|string|max:100',
            'agent_split_percentage' => 'sometimes|numeric|min:0|max:100',
            'rate_per_member' => 'sometimes|numeric|min:0',
            'members_count' => 'sometimes|integer|min:1',
            'effective_date' => 'sometimes|nullable|date',
            'notes' => 'sometimes|nullable|string',
        ]);

        $members = $request->has('members_count') ? (int) $request->input('members_count') : $commission->members_count;
        $rate = $request->has('rate_per_member') ? (float) $request->input('rate_per_member') : $commission->rate_per_member;
        $splitPct = $request->has('agent_split_percentage') ? (float) $request->input('agent_split_percentage') : $commission->agent_split_percentage;

        $gross = round($rate * $members, 2);
        $agentMonthly = round($gross * ($splitPct / 100), 2);
        $agencyMonthly = round($gross - $agentMonthly, 2);

        $validated['members_count'] = $members;
        $validated['rate_per_member'] = $rate;
        $validated['gross_monthly'] = $gross;
        $validated['agent_split_percentage'] = $splitPct;
        $validated['agent_monthly'] = $agentMonthly;
        $validated['agency_monthly'] = $agencyMonthly;

        $commission->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Comisión actualizada correctamente.',
            'commission' => $commission->load(['lead.person', 'user']),
        ]);
    }

    /**
     * Delete commission record.
     */
    public function destroy(int $id): JsonResponse
    {
        $commission = InsuranceCommission::findOrFail($id);
        $commission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro de comisión eliminado.',
        ]);
    }

    /**
     * Update default carrier rate.
     */
    public function updateRate(Request $request, int $id): JsonResponse
    {
        $rate = InsuranceCommissionRate::findOrFail($id);

        $validated = $request->validate([
            'rate_per_member' => 'required|numeric|min:0',
        ]);

        $rate->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Tarifa para {$rate->carrier_name} actualizada.",
            'rate' => $rate,
        ]);
    }

    /**
     * Reconciliation dashboard & statement history.
     */
    public function reconciliationIndex(Request $request): View|JsonResponse
    {
        $statements = CarrierStatement::with(['items', 'user'])->latest()->get();
        $rates = InsuranceCommissionRate::all();

        $totalPaidCarrier = $statements->sum('total_carrier_amount');
        $totalMissedDiscovered = $statements->sum('total_missed_amount');
        $totalStatementsCount = $statements->count();
        $averageMatchRate = $statements->avg('match_rate') ?: 0;

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'statements' => $statements,
                'kpis' => [
                    'total_paid' => $totalPaidCarrier,
                    'total_missed' => $totalMissedDiscovered,
                    'total_statements' => $totalStatementsCount,
                    'avg_match_rate' => round($averageMatchRate, 1),
                ],
            ]);
        }

        return view('admin::insurance.commissions.reconciliation', compact(
            'statements',
            'rates',
            'totalPaidCarrier',
            'totalMissedDiscovered',
            'totalStatementsCount',
            'averageMatchRate'
        ));
    }

    /**
     * Upload and reconcile a carrier statement CSV.
     */
    public function uploadStatement(Request $request, CommissionReconciliationService $service): JsonResponse
    {
        $validated = $request->validate([
            'carrier_name' => 'required|string|max:100',
            'period_month' => 'required|string|max:7',
            'statement_file' => 'nullable|file|mimes:csv,txt',
            'csv_content' => 'nullable|string',
        ]);

        $csvContent = '';
        $originalFileName = 'statement.csv';

        if ($request->hasFile('statement_file')) {
            $file = $request->file('statement_file');
            $originalFileName = $file->getClientOriginalName();
            $csvContent = file_get_contents($file->getRealPath());
        } elseif ($request->filled('csv_content')) {
            $csvContent = (string) $request->input('csv_content');
            $originalFileName = 'paste_'.$validated['carrier_name'].'_'.$validated['period_month'].'.csv';
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Debe adjuntar un archivo CSV o proporcionar el contenido del statement.',
            ], 422);
        }

        $userId = auth()->guard('user')->id();

        $statement = $service->reconcileCsv(
            $csvContent,
            $validated['carrier_name'],
            $validated['period_month'],
            $userId,
            $originalFileName
        );

        return response()->json([
            'success' => true,
            'message' => "Statement de {$statement->carrier_name} procesado. Se conciliaron {$statement->matched_records} pólizas y se identificaron {$statement->missed_records} comisiones omitidas.",
            'statement' => $statement->load(['items.lead.person', 'items.commission']),
        ]);
    }

    /**
     * Show statement details with all items.
     */
    public function showStatement(int $id): JsonResponse
    {
        $statement = CarrierStatement::with(['items.lead.person', 'items.commission', 'user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'statement' => $statement,
        ]);
    }

    /**
     * Delete statement record and its line items.
     */
    public function deleteStatement(int $id): JsonResponse
    {
        $statement = CarrierStatement::findOrFail($id);
        $statement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Estado de cuenta eliminado correctamente.',
        ]);
    }
}
