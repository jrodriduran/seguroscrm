<?php

namespace Webkul\Admin\Http\Controllers\Lead;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Services\LeadAiScoringService;

class LeadAiInsightsController extends Controller
{
    public function __construct(
        protected LeadAiScoringService $scoringService
    ) {}

    /**
     * Return evaluated AI score, tier, and Next Best Actions for a lead.
     */
    public function show(int $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $evaluation = $this->scoringService->evaluateLead($lead);

        return response()->json([
            'success' => true,
            'evaluation' => $evaluation,
        ]);
    }
}
