<?php

namespace Webkul\Lead\Services;

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

        // 1. Create statement record
        $statement = CarrierStatement::create([
            'carrier_name' => $carrierName,
            'file_name' => $fileName,
            'period_month' => $periodMonth,
            'status' => 'processing',
            'user_id' => $userId,
        ]);

        // 2. Parse CSV
        $rows = $this->parseCsv($csvContent);

        // 3. Retrieve all active commissions for this carrier
        $activeCommissions = InsuranceCommission::with(['lead.person', 'user'])
            ->where('carrier_name', 'LIKE', "%{$carrierName}%")
            ->get();

        $matchedCommissionIds = [];
        $totalCarrierAmount = 0.0;
        $totalExpectedAmount = 0.0;
        $totalMissedAmount = 0.0;

        $matchedCount = 0;
        $discrepancyCount = 0;
        $missedCount = 0;

        // 4. Process statement rows
        foreach ($rows as $row) {
            $policyNumber = trim((string) ($row['policy_number'] ?? ''));
            $insuredName = trim((string) ($row['insured_name'] ?? ''));
            $paidAmount = (float) ($row['carrier_amount'] ?? 0.0);

            if (empty($policyNumber) && empty($insuredName)) {
                continue;
            }

            // Find match in CRM
            $comm = $this->findMatchingCommission($activeCommissions, $policyNumber, $insuredName);

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

                CarrierStatementItem::create([
                    'carrier_statement_id' => $statement->id,
                    'policy_number' => $policyNumber ?: ($comm->policy_number ?: ('#POL-'.$comm->id)),
                    'insured_name' => $insuredName ?: ($comm->lead?->person?->name ?: $comm->lead?->title),
                    'carrier_amount' => $paidAmount,
                    'expected_amount' => $expected,
                    'difference' => $diff,
                    'match_status' => $status,
                    'commission_id' => $comm->id,
                    'lead_id' => $comm->lead_id,
                    'notes' => $status === 'matched_variance' ? 'Discrepancia detectada en monto pagado por la aseguradora.' : null,
                ]);

                $totalCarrierAmount += $paidAmount;
                $totalExpectedAmount += $expected;
            } else {
                // Not in CRM (Orphan policy or Chargeback on untracked policy)
                $status = ($paidAmount < 0) ? 'chargeback' : 'unmatched_orphan';
                $discrepancyCount++;

                CarrierStatementItem::create([
                    'carrier_statement_id' => $statement->id,
                    'policy_number' => $policyNumber ?: 'DESCONOCIDO',
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

        // 5. DETECT MISSED COMMISSIONS (Active in CRM but NOT paid in this statement)
        $unpaidCommissions = $activeCommissions->whereNotIn('id', $matchedCommissionIds);

        foreach ($unpaidCommissions as $unpaid) {
            $expected = (float) $unpaid->gross_monthly;
            $diff = -$expected;

            CarrierStatementItem::create([
                'carrier_statement_id' => $statement->id,
                'policy_number' => $unpaid->policy_number ?: ('#CRM-POL-'.$unpaid->id),
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

        // 6. Finalize statement summary
        $statement->update([
            'total_records' => $totalRecords,
            'matched_records' => $matchedCount,
            'discrepancy_records' => $discrepancyCount,
            'missed_records' => $missedCount,
            'total_carrier_amount' => round($totalCarrierAmount, 2),
            'total_expected_amount' => round($totalExpectedAmount, 2),
            'total_missed_amount' => round($totalMissedAmount, 2),
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
