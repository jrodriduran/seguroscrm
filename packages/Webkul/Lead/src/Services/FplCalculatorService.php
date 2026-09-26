<?php

namespace Webkul\Lead\Services;

class FplCalculatorService
{
    /**
     * HHS Poverty Guidelines Base (Size 1) and Additional person increments by year and region.
     */
    protected array $guidelines = [
        2026 => [
            'contiguous' => ['base' => 15960, 'additional' => 5600],
            'AK'         => ['base' => 19950, 'additional' => 7000],
            'HI'         => ['base' => 18350, 'additional' => 6440],
        ],
        2025 => [
            'contiguous' => ['base' => 15650, 'additional' => 5500],
            'AK'         => ['base' => 19570, 'additional' => 6880],
            'HI'         => ['base' => 17990, 'additional' => 6330],
        ],
        2024 => [
            'contiguous' => ['base' => 15060, 'additional' => 5380],
            'AK'         => ['base' => 18810, 'additional' => 6730],
            'HI'         => ['base' => 17310, 'additional' => 6190],
        ],
    ];

    /**
     * Calculate FPL, CSR tier, and estimated ACA subsidies.
     *
     * @param int $householdSize Total tax household members (e.g. 3)
     * @param float $annualIncome Projected Modified Adjusted Gross Income (MAGI)
     * @param string $stateCode 2-letter US state code (default 'FL')
     * @param int $taxYear Tax year (default 2026)
     * @param int|null $applyingMembers Number of members seeking coverage (defaults to householdSize)
     * @param float|null $customBenchmark Monthly gross benchmark premium (defaults to $480/member)
     * @return array
     */
    public function calculate(
        int $householdSize,
        float $annualIncome,
        string $stateCode = 'FL',
        int $taxYear = 2026,
        ?int $applyingMembers = null,
        ?float $customBenchmark = null
    ): array {
        $householdSize = max(1, $householdSize);
        $annualIncome = max(0.0, $annualIncome);
        $stateCode = strtoupper(trim($stateCode ?: 'FL'));
        $taxYear = in_array($taxYear, [2024, 2025, 2026]) ? $taxYear : 2026;
        $applyingMembers = max(1, $applyingMembers ?? $householdSize);

        // 1. Get HHS Poverty Guideline Threshold
        $regionKey = match ($stateCode) {
            'AK' => 'AK',
            'HI' => 'HI',
            default => 'contiguous',
        };

        $yearData = $this->guidelines[$taxYear][$regionKey];
        $threshold = (float) ($yearData['base'] + (($householdSize - 1) * $yearData['additional']));

        // 2. Compute FPL Percentage
        $fplPercentage = $threshold > 0 ? round(($annualIncome / $threshold) * 100, 1) : 0.0;

        // 3. Determine CSR Category & Tier
        [$fplCategory, $csrTier, $csrDescription] = $this->determineCsrCategory($fplPercentage, $stateCode);

        // 4. Compute ACA/IRA Applicable Contribution Percentage
        $applicablePercentage = $this->computeApplicablePercentage($fplPercentage);

        // 5. Compute Financial Contributions
        $maxAnnualContribution = round($annualIncome * ($applicablePercentage / 100), 2);
        $maxMonthlyContribution = round($maxAnnualContribution / 12, 2);

        // 6. Benchmark & APTC Calculations
        $benchmarkPerPerson = 480.00; // Average Benchmark Silver (SLCSP) monthly premium
        $totalBenchmarkMonthly = $customBenchmark !== null && $customBenchmark > 0
            ? round($customBenchmark, 2)
            : round($benchmarkPerPerson * $applyingMembers, 2);

        // Subsidies available if >= 100% FPL (or in expansion states <138% with exceptions)
        if ($fplCategory === 'medicaid_gap') {
            $estimatedMonthlyAptc = 0.0;
            $estimatedNetPremium = $totalBenchmarkMonthly;
        } else {
            $estimatedMonthlyAptc = max(0.0, round($totalBenchmarkMonthly - $maxMonthlyContribution, 2));
            $estimatedNetPremium = max(0.0, round($totalBenchmarkMonthly - $estimatedMonthlyAptc, 2));
        }

        return [
            'household_size'               => $householdSize,
            'applying_members'             => $applyingMembers,
            'projected_annual_income'      => $annualIncome,
            'state_code'                   => $stateCode,
            'tax_year'                     => $taxYear,
            'fpl_guideline_threshold'      => $threshold,
            'fpl_percentage'               => $fplPercentage,
            'fpl_category'                 => $fplCategory,
            'csr_tier'                     => $csrTier,
            'csr_description'              => $csrDescription,
            'applicable_percentage'        => $applicablePercentage,
            'max_annual_contribution'      => $maxAnnualContribution,
            'max_monthly_contribution'     => $maxMonthlyContribution,
            'estimated_benchmark_premium'  => $totalBenchmarkMonthly,
            'estimated_monthly_aptc'       => $estimatedMonthlyAptc,
            'estimated_net_premium'        => $estimatedNetPremium,
            'is_zero_premium_eligible'     => $estimatedNetPremium <= 0.0 && $fplCategory !== 'medicaid_gap',
        ];
    }

    /**
     * Determine CSR Tier and Federal Category.
     */
    protected function determineCsrCategory(float $fpl, string $stateCode): array
    {
        if ($fpl < 100.0) {
            return [
                'medicaid_gap',
                'Medicaid Gap (< 100%)',
                'Ingreso por debajo del 100% FPL. En estados sin expansión (como FL y TX) no califica a subsidios Marketplace a menos que aplique excepción de estatus migratorio.',
            ];
        }

        if ($fpl <= 150.0) {
            return [
                'silver_94',
                'Silver CSR 94% (Variante 06)',
                'Máxima reducción de costos compartidos. Deducible de $0 en casi todos los planes Silver, copagos médicos mínimos y prima neta de $0/mes.',
            ];
        }

        if ($fpl <= 200.0) {
            return [
                'silver_87',
                'Silver CSR 87% (Variante 05)',
                'Alta reducción de costos compartidos. Deducibles muy reducidos ($500 - $1,500) y copagos bajos en consultas y medicamentos preferidos.',
            ];
        }

        if ($fpl <= 250.0) {
            return [
                'silver_73',
                'Silver CSR 73% (Variante 04)',
                'Reducción moderada de costos compartidos. Deducibles y desembolso máximo ligeramente inferiores al estándar.',
            ];
        }

        return [
            'standard',
            'Estándar (Sin CSR)',
            'Ingreso superior al 250% FPL. Aplica subsidio APTC si la prima supera el límite de contribución (8.5% máx bajo IRA), pero sin variantes CSR reducidas.',
        ];
    }

    /**
     * Compute ACA / Inflation Reduction Act (IRA) applicable income percentage.
     */
    protected function computeApplicablePercentage(float $fpl): float
    {
        if ($fpl < 100.0) {
            return 0.0;
        }

        if ($fpl <= 150.0) {
            return 0.0; // 0% required contribution for 100-150% FPL
        }

        if ($fpl <= 200.0) {
            // Linear scale from 0.0% to 2.0%
            return round((($fpl - 150.0) / 50.0) * 2.0, 2);
        }

        if ($fpl <= 250.0) {
            // Linear scale from 2.0% to 4.0%
            return round(2.0 + ((($fpl - 200.0) / 50.0) * 2.0), 2);
        }

        if ($fpl <= 300.0) {
            // Linear scale from 4.0% to 6.0%
            return round(4.0 + ((($fpl - 250.0) / 50.0) * 2.0), 2);
        }

        if ($fpl <= 400.0) {
            // Linear scale from 6.0% to 8.5%
            return round(6.0 + ((($fpl - 300.0) / 100.0) * 2.5), 2);
        }

        // Above 400% FPL: Capped at 8.5% under IRA rules
        return 8.5;
    }
}
