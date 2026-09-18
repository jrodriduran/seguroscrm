<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\User\Models\User;

class CarrierStatement extends Model
{
    protected $table = 'carrier_statements';

    protected $fillable = [
        'carrier_name',
        'file_name',
        'file_path',
        'period_month',
        'total_records',
        'matched_records',
        'discrepancy_records',
        'missed_records',
        'total_carrier_amount',
        'total_expected_amount',
        'total_missed_amount',
        'status',
        'user_id',
    ];

    protected $casts = [
        'total_records' => 'integer',
        'matched_records' => 'integer',
        'discrepancy_records' => 'integer',
        'missed_records' => 'integer',
        'total_carrier_amount' => 'float',
        'total_expected_amount' => 'float',
        'total_missed_amount' => 'float',
    ];

    protected $appends = [
        'match_rate',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CarrierStatementItem::class, 'carrier_statement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Percentage of records that matched cleanly.
     */
    public function getMatchRateAttribute(): float
    {
        if ($this->total_records <= 0) {
            return 0.0;
        }

        return round(($this->matched_records / $this->total_records) * 100, 1);
    }
}
