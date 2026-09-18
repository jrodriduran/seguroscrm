<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\SlaRule as SlaRuleContract;

class SlaRule extends Model implements SlaRuleContract
{
    /**
     * Table name.
     */
    protected $table = 'lead_sla_rules';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lead_pipeline_id',
        'lead_type_id',
        'first_contact_hours',
        'follow_up_hours',
        'escalation_hours',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active'           => 'boolean',
        'first_contact_hours' => 'integer',
        'follow_up_hours'     => 'integer',
        'escalation_hours'    => 'integer',
    ];

    /**
     * Get the pipeline associated with the rule.
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(PipelineProxy::modelClass(), 'lead_pipeline_id');
    }

    /**
     * Get the lead type associated with the rule.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeProxy::modelClass(), 'lead_type_id');
    }
}
