<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Models\AgencyHierarchy;
use Webkul\Lead\Models\PolicyOverrideDistribution;
use Webkul\Lead\Services\AgencyOverrideService;
use Webkul\User\Models\User;

class AgencyHierarchyController extends Controller
{
    public function __construct(
        protected AgencyOverrideService $overrideService
    ) {}

    /**
     * Display agency hierarchy tree, downlines and overrides ledger.
     */
    public function index(Request $request): View|JsonResponse
    {
        $currentMonth = $request->input('period', Carbon::now()->format('Y-m'));

        $tree = $this->overrideService->getHierarchyTree();
        $users = User::all();
        $hierarchies = AgencyHierarchy::with(['user', 'parentUser'])->get();

        $totalOverridesDistributed = (float) PolicyOverrideDistribution::where('period_month', $currentMonth)
            ->sum('override_amount');

        $totalSubAgencies = AgencyHierarchy::whereIn('agency_tier', ['mga', 'ga'])->count();
        $totalProducers = AgencyHierarchy::whereIn('agency_tier', ['producer', 'sub_agent'])->count();

        $tierLabels = AgencyHierarchy::getTierLabels();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'tree' => $tree,
                'hierarchies' => $hierarchies,
                'total_overrides' => round($totalOverridesDistributed, 2),
                'total_sub_agencies' => $totalSubAgencies,
                'total_producers' => $totalProducers,
            ]);
        }

        return view('admin::insurance.hierarchy.index', [
            'tree' => $tree,
            'users' => $users,
            'hierarchies' => $hierarchies,
            'tierLabels' => $tierLabels,
            'currentMonth' => $currentMonth,
            'totalOverrides' => round($totalOverridesDistributed, 2),
            'totalSubAgencies' => $totalSubAgencies,
            'totalProducers' => $totalProducers,
        ]);
    }

    /**
     * Set or update agency tier, upline manager and override rate for an agent.
     */
    public function saveHierarchy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'parent_user_id' => 'nullable|integer|exists:users,id|different:user_id',
            'agency_tier' => 'required|string|in:fmo,mga,ga,producer,sub_agent',
            'sub_agency_name' => 'nullable|string|max:150',
            'npn_number' => 'nullable|string|max:30',
            'contract_level_percentage' => 'nullable|numeric|min:0|max:100',
            'override_pmpm' => 'nullable|numeric|min:0',
            'override_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $hierarchy = AgencyHierarchy::updateOrCreate(
            ['user_id' => $validated['user_id']],
            [
                'parent_user_id' => $validated['parent_user_id'] ?? null,
                'agency_tier' => $validated['agency_tier'],
                'sub_agency_name' => $validated['sub_agency_name'] ?? null,
                'npn_number' => $validated['npn_number'] ?? null,
                'contract_level_percentage' => $validated['contract_level_percentage'] ?? 70.0,
                'override_pmpm' => $validated['override_pmpm'] ?? 0.0,
                'override_percentage' => $validated['override_percentage'] ?? 0.0,
                'status' => 'active',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Estructura jerárquica y tasa de override guardadas correctamente.',
            'hierarchy' => $hierarchy->load(['user', 'parentUser']),
        ]);
    }

    /**
     * Get multi-tier policy overrides ledger.
     */
    public function overridesLedger(Request $request): JsonResponse
    {
        $query = PolicyOverrideDistribution::with(['policy', 'writingAgent', 'beneficiaryUser']);

        if ($request->filled('period')) {
            $query->where('period_month', $request->period);
        }

        if ($request->filled('beneficiary_id')) {
            $query->where('beneficiary_user_id', $request->beneficiary_id);
        }

        $distributions = $query->latest('id')->paginate(30);

        return response()->json([
            'success' => true,
            'distributions' => $distributions,
        ]);
    }

    /**
     * Export monthly override compensation statement for sub-agencies.
     */
    public function exportReport(Request $request): StreamedResponse
    {
        $period = $request->input('period', Carbon::now()->format('Y-m'));

        $distributions = PolicyOverrideDistribution::with(['policy', 'writingAgent', 'beneficiaryUser'])
            ->where('period_month', $period)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Reporte_Overrides_Downlines_{$period}.csv\"",
        ];

        return response()->stream(function () use ($distributions) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'ID Distribución',
                'Mes Período',
                'Upline Beneficiario (GA/MGA)',
                'Nivel Jerárquico',
                'Agente Escritor (Downline)',
                'N° Póliza',
                'Aseguradora',
                'Vidas Aseguradas',
                'Tarifa Override ($ PMPM)',
                'Monto Total Override ($)',
                'Estatus',
            ]);

            foreach ($distributions as $dist) {
                fputcsv($handle, [
                    $dist->id,
                    $dist->period_month,
                    $dist->beneficiaryUser?->name ?: 'N/A',
                    $dist->tier_name,
                    $dist->writingAgent?->name ?: 'N/A',
                    $dist->policy?->policy_number ?: 'N/A',
                    $dist->policy?->carrier_name ?: 'N/A',
                    $dist->members_count,
                    number_format($dist->rate_per_member, 2),
                    number_format($dist->override_amount, 2),
                    strtoupper($dist->status),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
