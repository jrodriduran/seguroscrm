<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Webkul\Lead\Models\AgentCommissionBalance;
use Webkul\Lead\Models\AgentCommissionLedgerTransaction;
use Webkul\Lead\Services\AgentLedgerService;
use Webkul\User\Models\User;

class AgentLedgerController extends Controller
{
    public function __construct(
        protected AgentLedgerService $ledgerService
    ) {}

    /**
     * Ledger index (Agency overview or JSON data).
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            $data = $this->ledgerService->getAgencyLedgerOverview();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('admin::insurance.ledger.index');
    }

    /**
     * Get specific agent's balance and full ledger transaction history.
     */
    public function agentHistory(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $balance = $this->ledgerService->getOrCreateBalance($userId);

        $transactions = AgentCommissionLedgerTransaction::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'agent' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Post a clawback debit against an agent.
     */
    public function recordClawback(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'policy_number' => 'required|string|max:80',
            'carrier_name' => 'nullable|string|max:100',
            'period_month' => 'nullable|string|max:7',
            'reason' => 'nullable|string|max:500',
            'policy_id' => 'nullable|integer',
        ]);

        $actorUserId = auth()->guard('user')->id();

        $transaction = $this->ledgerService->recordClawback(
            userId: $userId,
            amount: (float) $validated['amount'],
            policyNumber: $validated['policy_number'],
            carrierName: $validated['carrier_name'] ?? null,
            periodMonth: $validated['period_month'] ?? null,
            reason: $validated['reason'] ?? null,
            policyId: $validated['policy_id'] ?? null,
            actorUserId: $actorUserId
        );

        $balance = $this->ledgerService->getOrCreateBalance($userId);

        return response()->json([
            'success' => true,
            'message' => "Clawback de \${$validated['amount']} aplicado al agente.",
            'transaction' => $transaction,
            'balance' => $balance,
        ]);
    }

    /**
     * Disburse commission payout to an agent.
     */
    public function disburse(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reference_code' => 'required|string|max:80',
            'notes' => 'nullable|string|max:500',
        ]);

        $actorUserId = auth()->guard('user')->id();

        try {
            $transaction = $this->ledgerService->disbursePayout(
                userId: $userId,
                amount: (float) $validated['amount'],
                referenceCode: $validated['reference_code'],
                notes: $validated['notes'] ?? null,
                actorUserId: $actorUserId
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $balance = $this->ledgerService->getOrCreateBalance($userId);

        return response()->json([
            'success' => true,
            'message' => "Desembolso de \${$validated['amount']} liquidado con éxito.",
            'transaction' => $transaction,
            'balance' => $balance,
        ]);
    }

    /**
     * Apply an accounting adjustment.
     */
    public function adjustment(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'required|string|max:500',
        ]);

        $actorUserId = auth()->guard('user')->id();

        $transaction = $this->ledgerService->recordAdjustment(
            userId: $userId,
            signedAmount: (float) $validated['amount'],
            description: $validated['description'],
            actorUserId: $actorUserId
        );

        $balance = $this->ledgerService->getOrCreateBalance($userId);

        return response()->json([
            'success' => true,
            'message' => 'Ajuste contable aplicado.',
            'transaction' => $transaction,
            'balance' => $balance,
        ]);
    }
}
