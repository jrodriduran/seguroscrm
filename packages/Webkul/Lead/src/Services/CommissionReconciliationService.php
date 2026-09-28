<?php

namespace Webkul\Lead\Services;

use Webkul\Lead\Exceptions\DuplicateStatementException;
use Webkul\Lead\Models\CarrierStatement;
use Webkul\Lead\Models\CarrierStatementItem;
use Webkul\Lead\Models\InsuranceCommission;

class CommissionReconciliationService
{
    /**
     * Reconcile an uploaded carrier statement against active CRM policies.
     */
    public function reconcileCsv(
        string $csvContent,
        string $carrierName,
        string $periodMonth,
        ?int $userId = null,
        ?string $originalFileName = null
    ): CarrierStatement {
        $fileName = $originalFileName ?: 'statement_'.$carrierName.'_'.$periodMonth.'.csv';

        // 1. Compute SHA-256 hash for file idempotency
        $fileHash = hash('sha256', trim($csvContent));

        // Check if this exact file content has already been processed
        $existing = CarrierStatement::where('file_hash', $fileHash)->first();
        if ($existing) {
            throw new DuplicateStatementException($existing, $fileHash);
        }

        // 2. Create statement record
        $statement = CarrierStatement::create([
            'carrier_name' => $carrierName,
            'file_name' => $fileName,
            'file_hash' => $fileHash,
            'period_month' => $periodMonth,
            'status' => 'processing',
            'user_id' => $userId,
        ]);

        // 3. Parse CSV
        $rows = $this->parseCsv($csvContent);

        // 4. Retrieve all active commissions for this carrier
        $activeCommissions = InsuranceCommission::with(['lead.person', 'user'])
            ->where('carrier_name', 'LIKE', "%{$carrierName}%")
            ->get();

        $matchedCommissionIds = [];
        $totalCarrierAmount = 0.0;
        $totalExpectedAmount = 0.0;
        $totalMissedAmount = 0.0;
        $totalDuplicateAmount = 0.0;

        $matchedCount = 0;
        $discrepancyCount = 0;
        $missedCount = 0;
        $duplicateCount = 0;

        $seenPolicyNumbersInBatch = [];

        // 5. Process statement rows
        foreach ($rows as $row) {
            $policyNumber = trim((string) ($row['policy_number'] ?? ''));
            $insuredName = trim((string) ($row['insured_name'] ?? ''));
            $paidAmount = (float) ($row['carrier_amount'] ?? 0.0);

            if (empty($policyNumber) && empty($insuredName)) {
                continue;
            }

            // Find match in CRM
            $comm = $this->findMatchingCommission($activeCommissions, $policyNumber, $insuredName);

            // IDEMPOTENCY CHECK: Block duplicate payout attempts for carrier + policy_number + period_month
            if (! empty($policyNumber)) {
                $priorPayout = CarrierStatementItem::whereHas('statement', function ($q) use ($carrierName, $periodMonth) {
                    $q->where('carrier_name', $carrierName)
                        ->where('period_month', $periodMonth)
                        ->where('status', 'completed');
                })->where('policy_number', $policyNumber)
                    ->whereIn('match_status', ['matched_exact', 'matched_variance'])
                    ->first();

                if ($priorPayout || in_array($policyNumber, $seenPolicyNumbersInBatch, true)) {
                    $duplicateCount++;
                    $totalDuplicateAmount += $paidAmount;

                    CarrierStatementItem::create([
                        'carrier_statement_id' => $statement->id,
                        'policy_number' => $policyNumber,
                        'period_month' => $periodMonth,
                        'insured_name' => $insuredName ?: ($comm?->lead?->person?->name ?: $comm?->lead?->title ?: 'No Registrado'),
                        'carrier_amount' => $paidAmount,
                        'expected_amount' => 0.0,
                        'difference' => $paidAmount,
                        'match_status' => 'duplicate_blocked',
                        'is_duplicate' => true,
                        'duplicate_of_item_id' => $priorPayout?->id,
                        'commission_id' => $comm?->id,
                        'lead_id' => $comm?->lead_id,
                        'notes' => $priorPayout
                            ? "Pago duplicado bloqueado: La póliza {$policyNumber} ya recibió comisión para el período {$periodMonth} en Statement #{$priorPayout->carrier_statement_id}."
                            : "Pago duplicado bloqueado: Registro duplicado dentro del mismo archivo para la póliza {$policyNumber}.",
                    ]);

                    continue;
                }

                $seenPolicyNumbersInBatch[] = $policyNumber;
            }

            if ($comm) {
                $matchedCommissionIds[] = $comm->id;
                $expected = (float) $comm->gross_monthly;
                $diff = round($paidAmount - $expected, 2);

                if ($paidAmount < 0) {
                    $status = 'chargeback';
                    $discrepancyCount++;
                } elseif (abs($diff) <= 0.05) {
                    $status = 'matched_exact';
                    $matchedCount++;
                    $comm->update(['status' => 'paid']);
                } else {
                    $status = 'matched_variance';
                    $discrepancyCount++;
                }

                $statementItem = CarrierStatementItem::create([
                    'carrier_statement_id' => $statement->id,
                    'policy_number' => $policyNumber ?: ($comm->policy_number ?: ('#POL-'.$comm->id)),
                    'period_month' => $periodMonth,
                    'insured_name' => $insuredName ?: ($comm->lead?->person?->name ?: $comm->lead?->title),
                    'carrier_amount' => $paidAmount,
                    'expected_amount' => $expected,
                    'difference' => $diff,
                    'match_status' => $status,
                    'commission_id' => $comm->id,
                    'lead_id' => $comm->lead_id,
                    'notes' => $status === 'matched_variance' ? 'Discrepancia detectada en monto pagado por la aseguradora.' : null,
                ]);

                // Sync to Agent Ledger (credits on exact match, debits on clawbacks)
                if ($comm->user_id) {
                    $ledgerService = app(AgentLedgerService::class);
                    $splitPercent = (float) ($comm->agent_split_percent ?: 70.0);

                    if ($status === 'matched_exact') {
                        $agentCredit = (float) ($comm->agent_monthly ?: ($paidAmount * ($splitPercent / 100.0)));
                        $ledgerService->recordCommissionCredit(
                            userId: $comm->user_id,
                            amount: $agentCredit,
                            policyNumber: $statementItem->policy_number,
                            carrierName: $carrierName,
                            periodMonth: $periodMonth,
                            statementId: $statement->id,
                            statementItemId: $statementItem->id,
                            actorUserId: $userId
                        );
                    } elseif ($status === 'chargeback') {
                        $agentClawback = abs($paidAmount) * ($splitPercent / 100.0);
                        $ledgerService->recordClawback(
                            userId: $comm->user_id,
                            amount: $agentClawback,
                            policyNumber: $statementItem->policy_number,
                            carrierName: $carrierName,
                            periodMonth: $periodMonth,
                            reason: "Clawback de comisión por liquidación negativa en statement {$carrierName}.",
                            statementItemId: $statementItem->id,
                            actorUserId: $userId
                        );
                    }
                }

                $totalCarrierAmount += $paidAmount;
                $totalExpectedAmount += $expected;
            } else {
                // Not in CRM (Orphan policy or Chargeback on untracked policy)
                $status = ($paidAmount < 0) ? 'chargeback' : 'unmatched_orphan';
                $discrepancyCount++;

                CarrierStatementItem::create([
                    'carrier_statement_id' => $statement->id,
                    'policy_number' => $policyNumber ?: 'DESCONOCIDO',
                    'period_month' => $periodMonth,
                    'insured_name' => $insuredName ?: 'No Registrado',
                    'carrier_amount' => $paidAmount,
                    'expected_amount' => 0.0,
                    'difference' => $paidAmount,
                    'match_status' => $status,
                    'commission_id' => null,
                    'lead_id' => null,
                    'notes' => $status === 'chargeback' ? 'Clawback de póliza cancelada.' : 'Póliza pagada por el carrier pero no registrada en el CRM.',
                ]);

                $totalCarrierAmount += $paidAmount;
            }
        }

        // 6. DETECT MISSED COMMISSIONS (Active in CRM but NOT paid in this statement)
        $unpaidCommissions = $activeCommissions->whereNotIn('id', $matchedCommissionIds);

        foreach ($unpaidCommissions as $unpaid) {
            $expected = (float) $unpaid->gross_monthly;
            $diff = -$expected;

            CarrierStatementItem::create([
                'carrier_statement_id' => $statement->id,
                'policy_number' => $unpaid->policy_number ?: ('#CRM-POL-'.$unpaid->id),
                'period_month' => $periodMonth,
                'insured_name' => $unpaid->lead?->person?->name ?: $unpaid->lead?->title ?: 'Asegurado Registrado',
                'carrier_amount' => 0.0,
                'expected_amount' => $expected,
                'difference' => $diff,
                'match_status' => 'missed_commission',
                'commission_id' => $unpaid->id,
                'lead_id' => $unpaid->lead_id,
                'notes' => 'Comisión omitida por la aseguradora. Póliza activa en el CRM que no recibió pago en este período.',
            ]);

            $missedCount++;
            $totalMissedAmount += $expected;
            $totalExpectedAmount += $expected;
        }

        $totalRecords = $statement->items()->count();

        // 7. Finalize statement summary
        $statement->update([
            'total_records' => $totalRecords,
            'matched_records' => $matchedCount,
            'discrepancy_records' => $discrepancyCount,
            'missed_records' => $missedCount,
            'duplicate_records' => $duplicateCount,
            'total_carrier_amount' => round($totalCarrierAmount, 2),
            'total_expected_amount' => round($totalExpectedAmount, 2),
            'total_missed_amount' => round($totalMissedAmount, 2),
            'total_duplicate_amount' => round($totalDuplicateAmount, 2),
            'status' => 'completed',
        ]);

        return $statement->load('items');
    }

    /**
     * Find matching commission in cached collection.
     */
    protected function findMatchingCommission($commissions, string $policyNumber, string $insuredName): ?InsuranceCommission
    {
        // 1. Exact match on policy_number
        if (! empty($policyNumber)) {
            $match = $commissions->first(function ($c) use ($policyNumber) {
                return ! empty($c->policy_number) && strcasecmp(trim($c->policy_number), $policyNumber) === 0;
            });

            if ($match) {
                return $match;
            }
        }

        // 2. Fuzzy match on client name
        if (! empty($insuredName)) {
            $cleanInsured = strtolower(trim($insuredName));

            $match = $commissions->first(function ($c) use ($cleanInsured) {
                $clientName = strtolower(trim($c->lead?->person?->name ?: $c->lead?->title ?: ''));
                if (empty($clientName)) {
                    return false;
                }

                return str_contains($cleanInsured, $clientName) || str_contains($clientName, $cleanInsured);
            });

            if ($match) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Parse arbitrary CSV into standard rows.
     */
    protected function parseCsv(string $csvContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csvContent));
        if (empty($lines)) {
            return [];
        }

        $headerLine = array_shift($lines);
        $delimiter = str_contains($headerLine, ';') ? ';' : ',';
        $headers = array_map(fn ($h) => strtolower(trim(str_replace(['"', "'"], '', $h))), str_getcsv($headerLine, $delimiter));

        // Detect column indices
        $policyIdx = null;
        $nameIdx = null;
        $amountIdx = null;

        foreach ($headers as $idx => $header) {
            if ($policyIdx === null && (str_contains($header, 'pol') || str_contains($header, 'contract') || str_contains($header, 'id') || str_contains($header, 'number'))) {
                $policyIdx = $idx;
            }
            if ($nameIdx === null && (str_contains($header, 'name') || str_contains($header, 'insured') || str_contains($header, 'client') || str_contains($header, 'titular') || str_contains($header, 'member'))) {
                $nameIdx = $idx;
            }
            if ($amountIdx === null && (str_contains($header, 'amount') || str_contains($header, 'paid') || str_contains($header, 'comm') || str_contains($header, 'monto') || str_contains($header, 'pago') || str_contains($header, 'total'))) {
                $amountIdx = $idx;
            }
        }

        // Fallbacks
        $policyIdx = $policyIdx ?? 0;
        $nameIdx = $nameIdx ?? 1;
        $amountIdx = $amountIdx ?? (count($headers) > 2 ? 2 : 1);

        $parsed = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $cols = str_getcsv($line, $delimiter);

            $policyVal = $cols[$policyIdx] ?? null;
            $nameVal = $cols[$nameIdx] ?? null;
            $amountVal = $cols[$amountIdx] ?? null;

            // Clean amount: remove $, spaces, commas
            $cleanAmount = preg_replace('/[^0-9.\-]/', '', (string) $amountVal);

            $parsed[] = [
                'policy_number' => $policyVal ? trim($policyVal) : '',
                'insured_name' => $nameVal ? trim($nameVal) : '',
                'carrier_amount' => is_numeric($cleanAmount) ? (float) $cleanAmount : 0.0,
            ];
        }

        return $parsed;
    }
}
