<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\LeadTaxHousehold as LeadTaxHouseholdContract;

class LeadTaxHousehold extends Model implements LeadTaxHouseholdContract
{
    protected $table = 'lead_tax_households';

    protected $fillable = [
        'lead_id',
        'tax_year',
        'state_code',
        'household_size',
        'projected_annual_income',
        'fpl_guideline_threshold',
        'fpl_percentage',
        'fpl_category',
        'csr_tier',
        'applicable_percentage',
        'max_annual_contribution',
        'max_monthly_contribution',
        'estimated_benchmark_premium',
        'estimated_monthly_aptc',
        'estimated_net_premium',
        'notes',
    ];

    protected $casts = [
        'tax_year' => 'integer',
        'household_size' => 'integer',
        'projected_annual_income' => 'float',
        'fpl_guideline_threshold' => 'float',
        'fpl_percentage' => 'float',
        'applicable_percentage' => 'float',
        'max_annual_contribution' => 'float',
        'max_monthly_contribution' => 'float',
        'estimated_benchmark_premium' => 'float',
        'estimated_monthly_aptc' => 'float',
        'estimated_net_premium' => 'float',
    ];

    protected $appends = [
        'csr_label',
        'fpl_badge_class',
    ];

    /**
     * Parent lead relationship.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass());
    }

    /**
     * Get a human-readable CSR tier label.
     */
    public function getCsrLabelAttribute(): string
    {
        return match ($this->fpl_category) {
            'silver_94' => 'Silver CSR 94% (Variante 06)',
            'silver_87' => 'Silver CSR 87% (Variante 05)',
            'silver_73' => 'Silver CSR 73% (Variante 04)',
            'medicaid_gap' => 'Medicaid Gap (< 100% FPL)',
            default => 'Estándar (Sin CSR)',
        };
    }

    /**
     * Tailwind / UI badge class for FPL status.
     */
    public function getFplBadgeClassAttribute(): string
    {
        return match ($this->fpl_category) {
            'silver_94' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-300',
            'silver_87' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-300',
            'silver_73' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border-indigo-300',
            'medicaid_gap' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-300',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border-gray-300',
        };
    }
}
