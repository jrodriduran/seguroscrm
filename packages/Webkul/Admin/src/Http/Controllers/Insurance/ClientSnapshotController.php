<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Services\ClientSnapshotService;

class ClientSnapshotController extends Controller
{
    public function __construct(
        protected ClientSnapshotService $snapshotService
    ) {}

    /**
     * Return AI-generated 360-degree executive snapshot for a lead.
     */
    public function show(int $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $snapshot = $this->snapshotService->generateSnapshot($lead);

        return response()->json([
            'success' => true,
            'snapshot' => $snapshot,
        ]);
    }
}
