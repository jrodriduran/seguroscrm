<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\AssignmentRule as AssignmentRuleContract;

class AssignmentRule extends Model implements AssignmentRuleContract
{
    /**
     * Table name.
     */
    protected $table = 'lead_assignment_rules';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lead_pipeline_id',
        'strategy',
        'max_capacity',
        'agent_ids',
        'rr_pointer',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'agent_ids' => 'array',
        'max_capacity' => 'integer',
        'rr_pointer' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the pipeline associated with the rule.
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(PipelineProxy::modelClass(), 'lead_pipeline_id');
    }
}
