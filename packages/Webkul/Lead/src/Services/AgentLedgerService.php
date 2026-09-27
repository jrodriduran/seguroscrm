<?php

namespace Webkul\Lead\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Webkul\Lead\Models\AgentCommissionBalance;
use Webkul\Lead\Models\AgentCommissionLedgerTransaction;
use Webkul\User\Models\User;

class AgentLedgerService
{
    /**
     * Get or create balance account for an agent.
     */
    public function getOrCreateBalance(int $userId): AgentCommissionBalance
    {
        return AgentCommissionBalance::firstOrCreate(
            ['user_id' => $userId],
            [
                'total_earned' => 0.00,
                'total_clawbacks' => 0.00,
                'total_paid_out' => 0.00,
                'current_balance' => 0.00,
                'held_reserve' => 0.00,
                'status' => 'in_good_standing',
            ]
        );
    }

    /**
     * Record a commission credit for an agent.
     */
    public function recordCommissionCredit(
        int $userId,
        float $amount,
        string $policyNumber,
        ?string $carrierName = null,
        ?string $periodMonth = null,
        ?int $policyId = null,
        ?int $statementId = null,
        ?int $statementItemId = null,
        ?int $actorUserId = null
    ): AgentCommissionLedgerTransaction {
        return $this->recordTransaction(
            userId: $userId,
            type: 'commission_credit',
            amount: abs($amount),
            meta: [
                'policy_number' => $policyNumber,
                'carrier_name' => $carrierName,
                'period_month' => $periodMonth,
                'policy_id' => $policyId,
                'statement_id' => $statementId,
                'statement_item_id' => $statementItemId,
                'description' => "Comisión acreditada PMPM ({$carrierName} - Póliza #{$policyNumber}).",
            ],
            actorUserId: $actorUserId
        );
    }

    /**
     * Record an override credit for an upline agent.
     */
    public function recordOverrideCredit(
        int $userId,
        float $amount,
        string $policyNumber,
        ?string $carrierName = null,
        ?string $periodMonth = null,
        ?int $policyId = null,
        ?int $actorUserId = null
    ): AgentCommissionLedgerTransaction {
        return $this->recordTransaction(
            userId: $userId,
            type: 'override_credit',
            amount: abs($amount),
            meta: [
                'policy_number' => $policyNumber,
                'carrier_name' => $carrierName,
                'period_month' => $periodMonth,
                'policy_id' => $policyId,
                'description' => "Sobrecomisión (Override) acreditada por producción de equipo (Póliza #{$policyNumber}).",
            ],
            actorUserId: $actorUserId
        );
    }

    /**
     * Record a clawback / chargeback debit on an agent's account.
     */
    public function recordClawback(
        int $userId,
        float $amount,
        string $policyNumber,
        ?string $carrierName = null,
        ?string $periodMonth = null,
        ?string $reason = null,
        ?int $policyId = null,
        ?int $statementItemId = null,
        ?int $actorUserId = null
    ): AgentCommissionLedgerTransaction {
        $positiveAmount = abs($amount);

        return $this->recordTransaction(
            userId: $userId,
            type: 'clawback_debit',
            amount: -$positiveAmount,
            meta: [
                'policy_number' => $policyNumber,
                'carrier_name' => $carrierName,
                'period_month' => $periodMonth,
                'policy_id' => $policyId,
                'statement_item_id' => $statementItemId,
                'description' => $reason ?: "Clawback / Cargo de retrocesión por cancelación anticipada (Póliza #{$policyNumber}).",
            ],
            actorUserId: $actorUserId
        );
    }

    /**
     * Disburse cash payout (e.g. ACH / check) to the agent.
     */
    public function disbursePayout(
        int $userId,
        float $amount,
        string $referenceCode,
        ?string $notes = null,
        ?int $actorUserId = null
    ): AgentCommissionLedgerTransaction {
        $payoutAmount = abs($amount);

        $balance = $this->getOrCreateBalance($userId);
        if ($balance->net_available_balance < $payoutAmount) {
            throw new InvalidArgumentException("Saldo insuficiente para liquidar. Saldo disponible: \${$balance->net_available_balance}, Monto solicitado: \${$payoutAmount}.");
        }

        return $this->recordTransaction(
            userId: $userId,
            type: 'payout_disbursement',
            amount: -$payoutAmount,
            meta: [
                'reference_code' => $referenceCode,
                'description' => $notes ?: "Desembolso de liquidación de comisiones Ref: {$referenceCode}.",
            ],
            actorUserId: $actorUserId
        );
    }

    /**
     * Record an accounting adjustment.
     */
    public function recordAdjustment(
        int $userId,
        float $signedAmount,
        string $description,
        ?int $actorUserId = null
    ): AgentCommissionLedgerTransaction {
        return $this->recordTransaction(
            userId: $userId,
            type: 'adjustment',
            amount: $signedAmount,
            meta: ['description' => $description],
            actorUserId: $actorUserId
        );
    }

    /**
     * Core atomic transaction recording with pessimistic row lock.
     */
    protected function recordTransaction(
        int $userId,
        string $type,
        float $amount,
        array $meta = [],
        ?int $actorUserId = null
    ): AgentCommissionLedgerTransaction {
        return DB::transaction(function () use ($userId, $type, $amount, $meta, $actorUserId) {
            // Lock balance row for update to guarantee strict concurrency & balance correctness
            $balance = AgentCommissionBalance::where('user_id', $userId)->lockForUpdate()->first();
            if (! $balance) {
                $balance = $this->getOrCreateBalance($userId);
                $balance = AgentCommissionBalance::where('user_id', $userId)->lockForUpdate()->first();
            }

            $before = (float) $balance->current_balance;
            $after = round($before + $amount, 2);

            // Update cumulative totals
            if ($type === 'commission_credit' || $type === 'override_credit') {
                $balance->total_earned += abs($amount);
            } elseif ($type === 'clawback_debit') {
                $balance->total_clawbacks += abs($amount);
            } elseif ($type === 'payout_disbursement') {
                $balance->total_paid_out += abs($amount);
                $balance->last_payout_at = now();
            } elseif ($type === 'adjustment') {
                if ($amount > 0) {
                    $balance->total_earned += $amount;
                } else {
                    $balance->total_clawbacks += abs($amount);
                }
            }

            $balance->current_balance = $after;
            $balance->status = $after < 0 ? 'negative_balance_alert' : 'in_good_standing';
            $balance->save();

            // Create ledger entry
            return AgentCommissionLedgerTransaction::create([
                'user_id' => $userId,
                'transaction_type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'period_month' => $meta['period_month'] ?? null,
                'carrier_name' => $meta['carrier_name'] ?? null,
                'policy_id' => $meta['policy_id'] ?? null,
                'policy_number' => $meta['policy_number'] ?? null,
                'statement_id' => $meta['statement_id'] ?? null,
                'statement_item_id' => $meta['statement_item_id'] ?? null,
                'reference_code' => $meta['reference_code'] ?? null,
                'description' => $meta['description'] ?? null,
                'created_by_user_id' => $actorUserId ?: auth()->guard('user')->id(),
            ]);
        });
    }

    /**
     * Get agency ledger overview with aggregate stats.
     */
    public function getAgencyLedgerOverview(): array
    {
        $balances = AgentCommissionBalance::with('user')->orderBy('current_balance', 'asc')->get();

        $totalPayable = 0.0;
        $totalNegativeDebt = 0.0;
        $totalEarnedAllTime = 0.0;
        $totalClawbacksAllTime = 0.0;
        $negativeCount = 0;

        foreach ($balances as $b) {
            $totalEarnedAllTime += (float) $b->total_earned;
            $totalClawbacksAllTime += (float) $b->total_clawbacks;

            if ($b->current_balance < 0) {
                $totalNegativeDebt += abs((float) $b->current_balance);
                $negativeCount++;
            } else {
                $totalPayable += (float) $b->current_balance;
            }
        }

        $recentTransactions = AgentCommissionLedgerTransaction::with('user')
            ->orderBy('id', 'desc')
            ->limit(15)
            ->get();

        return [
            'kpis' => [
                'total_agents' => $balances->count(),
                'total_payable_balance' => round($totalPayable, 2),
                'total_negative_debt' => round($totalNegativeDebt, 2),
                'agents_with_debt' => $negativeCount,
                'total_earned_all_time' => round($totalEarnedAllTime, 2),
                'total_clawbacks_all_time' => round($totalClawbacksAllTime, 2),
            ],
            'balances' => $balances,
            'recent_transactions' => $recentTransactions,
        ];
    }
}
