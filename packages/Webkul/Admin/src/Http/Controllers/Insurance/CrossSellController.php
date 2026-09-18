<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadCrossSellOpportunity;
use Webkul\Lead\Services\CrossSellOpportunityService;

class CrossSellController extends Controller
{
    public function __construct(
        protected CrossSellOpportunityService $crossSellService
    ) {}

    /**
     * Get evaluated cross-sell opportunities for a lead.
     */
    public function index(int $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $data = $this->crossSellService->evaluateAndSync($lead);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Update cross-sell opportunity status (e.g. presented, enrolled, declined).
     */
    public function updateStatus(Request $request, int $leadId, int $opportunityId): JsonResponse
    {
        $opportunity = LeadCrossSellOpportunity::where('lead_id', $leadId)->findOrFail($opportunityId);

        $request->validate([
            'status' => 'required|in:recommended,presented,enrolled,declined',
        ]);

        $opportunity->update([
            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estatus de venta cruzada actualizado.',
            'opportunity' => $opportunity,
        ]);
    }
}
