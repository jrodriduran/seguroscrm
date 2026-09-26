<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\PolicyCoverageStatusHistory as PolicyCoverageStatusHistoryContract;
use Webkul\User\Models\UserProxy;

class PolicyCoverageStatusHistory extends Model implements PolicyCoverageStatusHistoryContract
{
    protected $table = 'policy_coverage_status_histories';

    protected $fillable = [
        'policy_id',
        'lead_id',
        'from_status',
        'to_status',
        'binder_status',
        'source',
        'reason',
        'verified_by_user_id',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /**
     * Parent Policy relation.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'policy_id');
    }

    /**
     * Lead relation.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    /**
     * Verifying Agent / User relation.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'verified_by_user_id');
    }
}
