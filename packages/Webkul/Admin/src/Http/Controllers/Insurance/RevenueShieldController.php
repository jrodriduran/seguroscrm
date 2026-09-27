<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Lead\Services\RevenueShieldService;

class RevenueShieldController extends Controller
{
    public function __construct(
        protected RevenueShieldService $revenueShieldService
    ) {}

    /**
     * Render the Revenue Shield audit control panel.
     */
    public function index(Request $request): View
    {
        $userId = $request->has('all_agents') && auth()->user()->hasPermission('insurance.view_all_action_board')
            ? null
            : auth()->id();

        $summary = $this->revenueShieldService->getSummary($userId);

        return view('admin::insurance.revenue_shield.index', compact('summary'));
    }

    /**
     * Trigger manual execution of the Revenue Shield audit algorithm.
     */
    public function runScan(): JsonResponse
    {
        $results = $this->revenueShieldService->runAudit();

        return response()->json([
            'success' => true,
            'message' => "Auditoría Revenue Shield completada: {$results['flagged_missing']} póliza(s) detectadas con comisiones no reportadas.",
            'results' => $results,
        ]);
    }

    /**
     * Resolve a missing commission flag with agent claim reference.
     */
    public function resolve(Request $request, int $policyId): JsonResponse
    {
        $request->validate([
            'resolution_note' => 'required|string|max:1000',
            'claim_ticket' => 'nullable|string|max:100',
        ]);

        $this->revenueShieldService->resolveMissing(
            $policyId,
            $request->input('resolution_note'),
            $request->input('claim_ticket')
        );

        return response()->json([
            'success' => true,
            'message' => 'Alerta de comisión resuelta exitosamente.',
        ]);
    }
}
