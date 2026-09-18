<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceCommissionRate extends Model
{
    protected $table = 'insurance_commission_rates';

    protected $fillable = [
        'carrier_name',
        'metal_tier',
        'commission_type',
        'rate_per_member',
        'effective_year',
    ];

    protected $casts = [
        'rate_per_member' => 'float',
        'effective_year' => 'integer',
    ];

    /**
     * Look up default rate for carrier and metal tier.
     */
    public static function getRate(string $carrierName, ?string $metalTier = 'All', int $year = 2026): float
    {
        $rate = self::where('carrier_name', 'LIKE', "%{$carrierName}%")
            ->where(function ($q) use ($metalTier) {
                $q->where('metal_tier', $metalTier)
                    ->orWhere('metal_tier', 'All');
            })
            ->where('effective_year', $year)
            ->value('rate_per_member');

        return $rate !== null ? (float) $rate : 25.0;
    }
}
