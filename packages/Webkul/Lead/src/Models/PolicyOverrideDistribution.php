<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class PolicyOverrideDistribution extends Model
{
    protected $table = 'policy_override_distributions';

    protected $fillable = [
        'policy_id',
        'commission_id',
        'writing_agent_id',
        'beneficiary_user_id',
        'tier_level',
        'tier_name',
        'rate_per_member',
        'members_count',
        'override_amount',
        'period_month',
        'status',
    ];

    protected $casts = [
        'rate_per_member' => 'float',
        'override_amount' => 'float',
        'tier_level' => 'integer',
        'members_count' => 'integer',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'policy_id');
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(InsuranceCommission::class, 'commission_id');
    }

    public function writingAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'writing_agent_id');
    }

    public function beneficiaryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_user_id');
    }
}
