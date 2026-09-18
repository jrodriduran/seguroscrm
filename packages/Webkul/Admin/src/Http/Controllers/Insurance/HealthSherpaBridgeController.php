<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Activity\Models\Activity;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Services\HealthSherpaBridgeService;

class HealthSherpaBridgeController extends Controller
{
    public function __construct(
        protected HealthSherpaBridgeService $bridgeService
    ) {}

    /**
     * Get pre-filled JSON payload and deep-link for a lead.
     */
    public function getPayload(int $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $data = $this->bridgeService->generateBridgeData($lead);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Redirect agent directly to HealthSherpa with prefilled params and log activity.
     */
    public function redirect(int $leadId)
    {
        $lead = Lead::with('person')->findOrFail($leadId);

        $data = $this->bridgeService->generateBridgeData($lead);

        // Record activity for audit
        try {
            Activity::create([
                'title' => '🚀 Redirección a HealthSherpa (Prellenado EDE)',
                'type' => 'call',
                'comment' => "El agente inició la cotización/aplicación en HealthSherpa con censo de {$data['household_size']} miembros e ingresos de \${$data['annual_income']}.",
                'schedule_from' => Carbon::now(),
                'schedule_to' => Carbon::now(),
                'is_done' => 1,
                'user_id' => auth()->id() ?: 1,
                'lead_id' => $lead->id,
            ]);
        } catch (\Throwable $e) {}

        return redirect()->away($data['deep_link_url']);
    }
}
