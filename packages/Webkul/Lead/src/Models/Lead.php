<?php

namespace Webkul\Lead\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Activity\Models\ActivityProxy;
use Webkul\Activity\Traits\LogsActivity;
use Webkul\Attribute\Traits\CustomAttribute;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Email\Models\EmailProxy;
use Webkul\Lead\Contracts\Lead as LeadContract;
use Webkul\Quote\Models\QuoteProxy;
use Webkul\Tag\Models\TagProxy;
use Webkul\User\Models\UserProxy;

class Lead extends Model implements LeadContract
{
    use CustomAttribute, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'lead_value',
        'status',
        'lost_reason',
        'expected_close_date',
        'closed_at',
        'user_id',
        'person_id',
        'lead_source_id',
        'lead_type_id',
        'lead_pipeline_id',
        'lead_pipeline_stage_id',
        'sla_status',
        'assigned_at',
        'sla_hours',
        'escalated_at',
        'escalation_reason',
    ];

    /**
     * Cast the attributes to their respective types.
     *
     * @var array
     */
    protected $casts = [
        'closed_at' => 'datetime:D M d, Y H:i A',
        'expected_close_date' => 'date:D M d, Y',
        'assigned_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    /**
     * The attributes that are appended.
     *
     * @var array
     */
    protected $appends = [
        'rotten_days',
        'sla_status_badge',
    ];

    /**
     * Get the user that owns the lead.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass());
    }

    /**
     * Get the person that owns the lead.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass());
    }

    /**
     * Get the type that owns the lead.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeProxy::modelClass(), 'lead_type_id');
    }

    /**
     * Get the source that owns the lead.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(SourceProxy::modelClass(), 'lead_source_id');
    }

    /**
     * Get the pipeline that owns the lead.
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(PipelineProxy::modelClass(), 'lead_pipeline_id');
    }

    /**
     * Get the pipeline stage that owns the lead.
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(StageProxy::modelClass(), 'lead_pipeline_stage_id');
    }

    /**
     * Get the activities.
     */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(ActivityProxy::modelClass(), 'lead_activities');
    }

    /**
     * Get the products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(ProductProxy::modelClass());
    }

    /**
     * Get the emails.
     */
    public function emails(): HasMany
    {
        return $this->hasMany(EmailProxy::modelClass());
    }

    /**
     * The quotes that belong to the lead.
     */
    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(QuoteProxy::modelClass(), 'lead_quotes');
    }

    /**
     * The tags that belong to the lead.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TagProxy::modelClass(), 'lead_tags');
    }

    /**
     * Get the household members / dependents for the lead.
     */
    public function householdMembers(): HasMany
    {
        return $this->hasMany(HouseholdMemberProxy::modelClass());
    }

    /**
     * Get the CMS consent associated with the lead.
     */
    public function consent(): HasOne
    {
        return $this->hasOne(LeadConsentProxy::modelClass());
    }

    /**
     * Get the DMI documents associated with the lead.
     */
    public function dmiDocuments(): HasMany
    {
        return $this->hasMany(LeadDmiDocumentProxy::modelClass());
    }

    /**
     * Get the Medicare Scope of Appointment records associated with the lead.
     */
    public function medicareSoas(): HasMany
    {
        return $this->hasMany(LeadMedicareSoa::class, 'lead_id');
    }

    /**
     * Get the SEP / QLE qualification record for the lead.
     */
    public function sepQualification(): HasOne
    {
        return $this->hasOne(LeadSepQualification::class, 'lead_id');
    }

    /**
     * Returns the rotten days
     */
    public function getRottenDaysAttribute()
    {
        if (! $this->stage) {
            return 0;
        }

        if (in_array($this->stage->code, ['won', 'lost'])) {
            return 0;
        }

        if (! $this->created_at) {
            return 0;
        }

        $rottenDate = $this->created_at->addDays($this->pipeline->rotten_days);

        return $rottenDate->diffInDays(Carbon::now(), false);
    }

    /**
     * Returns a semantic badge identifier for the current SLA status.
     * Values: 'ok' | 'warning' | 'danger' | 'resolved'
     */
    public function getSlaStatusBadgeAttribute(): string
    {
        return match ($this->sla_status ?? 'pending') {
            'active' => 'ok',
            'overdue' => 'danger',
            'escalated' => 'danger',
            'resolved' => 'resolved',
            default => 'warning',
        };
    }

    /**
     * Scope: leads whose SLA has expired (overdue).
     */
    public function scopeOverdue($query)
    {
        return $query->where('sla_status', 'overdue');
    }

    /**
     * Scope: leads that have been escalated to Master Agent.
     */
    public function scopeEscalated($query)
    {
        return $query->where('sla_status', 'escalated');
    }

    /**
     * Scope: leads that do not have an agent assigned.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope: leads in an active SLA state.
     */
    public function scopeSlaActive($query)
    {
        return $query->where('sla_status', 'active');
    }

    /**
     * Scope: leads assigned to a specific agent.
     */
    public function scopeMyLeads($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: leads assigned today to a specific agent.
     */
    public function scopeAssignedToday($query, int $userId)
    {
        return $query
            ->where('user_id', $userId)
            ->whereDate('assigned_at', Carbon::today());
    }
}
