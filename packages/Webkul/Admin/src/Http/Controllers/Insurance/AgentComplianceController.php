<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Lead\Models\UserAgentLicense;
use Webkul\Lead\Services\AgentComplianceService;
use Webkul\User\Models\User;

class AgentComplianceController extends Controller
{
    public function __construct(
        protected AgentComplianceService $complianceService
    ) {}

    /**
     * Get compliance status, state licenses, and EO details for an agent.
     */
    public function show(int $userId): JsonResponse
    {
        $compliance = $this->complianceService->getAgentCompliance($userId);

        return response()->json([
            'success' => true,
            'compliance' => $compliance,
        ]);
    }

    /**
     * Add or update a state insurance license for an agent.
     */
    public function saveLicense(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'state_code' => 'required|string|size:2',
            'license_number' => 'required|string|max:60',
            'license_type' => 'required|in:resident,non_resident',
            'expires_at' => 'required|date',
        ]);

        $license = UserAgentLicense::updateOrCreate(
            ['user_id' => $userId, 'state_code' => strtoupper($request->input('state_code'))],
            [
                'license_number' => $request->input('license_number'),
                'license_type' => $request->input('license_type'),
                'expires_at' => $request->input('expires_at'),
                'status' => 'active',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Licencia estatal registrada exitosamente.',
            'license' => $license,
        ]);
    }

    /**
     * Update E&O policy and AHIP certification details for an agent.
     */
    public function updateEoAndAhip(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'npn' => 'nullable|string|max:30',
            'eo_carrier' => 'nullable|string|max:100',
            'eo_policy_number' => 'nullable|string|max:80',
            'eo_expires_at' => 'nullable|date',
            'ahip_certified_year' => 'nullable|integer|min:2020|max:2035',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Póliza de E&O y certificación AHIP actualizadas.',
            'user' => $user,
        ]);
    }
}
