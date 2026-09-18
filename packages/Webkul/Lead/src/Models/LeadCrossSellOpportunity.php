<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\LeadCrossSellOpportunity as LeadCrossSellOpportunityContract;

class LeadCrossSellOpportunity extends Model implements LeadCrossSellOpportunityContract
{
    protected $table = 'lead_cross_sell_opportunities';

    protected $fillable = [
        'lead_id',
        'product_type',
        'title',
        'carrier_name',
        'estimated_monthly_premium',
        'estimated_agent_commission',
        'status',
        'gap_reason',
    ];

    protected $casts = [
        'estimated_monthly_premium' => 'float',
        'estimated_agent_commission' => 'float',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass());
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'enrolled' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
            'presented' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300',
            'declined' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
            default => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300',
        };
    }
}
