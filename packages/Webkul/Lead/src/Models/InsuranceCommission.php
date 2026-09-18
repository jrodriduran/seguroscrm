<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Quote\Models\Quote;
use Webkul\User\Models\User;

class InsuranceCommission extends Model
{
    protected $table = 'insurance_commissions';

    protected $fillable = [
        'lead_id',
        'quote_id',
        'user_id',
        'policy_number',
        'carrier_name',
        'plan_name',
        'metal_tier',
        'members_count',
        'commission_type',
        'rate_per_member',
        'gross_monthly',
        'agent_split_percentage',
        'agent_monthly',
        'agency_monthly',
        'status',
        'effective_date',
        'notes',
    ];

    protected $casts = [
        'rate_per_member' => 'float',
        'gross_monthly' => 'float',
        'agent_split_percentage' => 'float',
        'agent_monthly' => 'float',
        'agency_monthly' => 'float',
        'effective_date' => 'date',
        'members_count' => 'integer',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Create or update commission from a converted quote/policy.
     */
    public static function createFromQuote(Quote $quote, ?float $customSplitPercentage = 70.0): self
    {
        $carrier = $quote->carrier_name ?: 'Salud';
        $tier = $quote->metal_tier ?: 'Silver';
        $members = max(1, (int) ($quote->household_members_count ?? 1));

        $rate = InsuranceCommissionRate::getRate($carrier, $tier);
        $gross = round($rate * $members, 2);

        $splitPct = $customSplitPercentage !== null ? (float) $customSplitPercentage : 70.0;
        $agentMonthly = round($gross * ($splitPct / 100), 2);
        $agencyMonthly = round($gross - $agentMonthly, 2);

        $lead = $quote->leads()->first();

        return self::create([
            'lead_id' => $lead?->id,
            'quote_id' => $quote->id,
            'user_id' => $quote->user_id ?: ($lead?->user_id ?: 1),
            'carrier_name' => $carrier,
            'plan_name' => $quote->plan_name,
            'metal_tier' => $tier,
            'members_count' => $members,
            'commission_type' => 'pmpm',
            'rate_per_member' => $rate,
            'gross_monthly' => $gross,
            'agent_split_percentage' => $splitPct,
            'agent_monthly' => $agentMonthly,
            'agency_monthly' => $agencyMonthly,
            'status' => 'active',
            'effective_date' => now()->startOfMonth(),
            'notes' => 'Generada automáticamente al emitir la póliza desde cotización #' . $quote->id,
        ]);
    }
}
