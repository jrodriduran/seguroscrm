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
        'period_month',
        'insured_name',
        'carrier_amount',
        'expected_amount',
        'difference',
        'match_status',
        'is_duplicate',
        'duplicate_of_item_id',
        'commission_id',
        'lead_id',
        'notes',
    ];

    protected $casts = [
        'carrier_amount' => 'float',
        'expected_amount' => 'float',
        'difference' => 'float',
        'is_duplicate' => 'boolean',
    ];

    protected $appends = [
        'status_label',
        'status_badge_class',
    ];

    public function statement(): BelongsTo
    {
        return $this->belongsTo(CarrierStatement::class, 'carrier_statement_id');
    }

    public function duplicateOfItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_item_id');
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
            'matched_exact' => trans('admin::insurance.reconciliation.status_matched_exact'),
            'matched_variance' => trans('admin::insurance.reconciliation.status_matched_variance'),
            'missed_commission' => trans('admin::insurance.reconciliation.status_missed_commission'),
            'unmatched_orphan' => trans('admin::insurance.reconciliation.status_unmatched_orphan'),
            'chargeback' => trans('admin::insurance.reconciliation.status_chargeback'),
            'duplicate_blocked' => trans('admin::insurance.reconciliation.status_duplicate_blocked'),
            default => ucfirst($this->match_status ?: 'unknown'),
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
            'duplicate_blocked' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-400 font-bold',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
