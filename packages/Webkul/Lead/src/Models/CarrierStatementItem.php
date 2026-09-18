<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierStatementItem extends Model
{
    protected $table = 'carrier_statement_items';

    protected $fillable = [
        'carrier_statement_id',
        'policy_number',
        'insured_name',
        'carrier_amount',
        'expected_amount',
        'difference',
        'match_status',
        'commission_id',
        'lead_id',
        'notes',
    ];

    protected $casts = [
        'carrier_amount' => 'float',
        'expected_amount' => 'float',
        'difference' => 'float',
    ];

    protected $appends = [
        'status_label',
        'status_badge_class',
    ];

    public function statement(): BelongsTo
    {
        return $this->belongsTo(CarrierStatement::class, 'carrier_statement_id');
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(InsuranceCommission::class, 'commission_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    /**
     * Human-friendly status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->match_status) {
            'matched_exact' => 'Conciliado Exacto',
            'matched_variance' => 'Variación / Discrepancia',
            'missed_commission' => 'Comisión Omitida (No Pagada)',
            'unmatched_orphan' => 'Póliza Huérfana (No en CRM)',
            'chargeback' => 'Chargeback / Clawback',
            default => 'Desconocido',
        };
    }

    /**
     * Badge CSS class for UI.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->match_status) {
            'matched_exact' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300',
            'matched_variance' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300',
            'missed_commission' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300 border border-red-300',
            'unmatched_orphan' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-300',
            'chargeback' => 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300 border border-orange-300',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
