<?php

namespace Webkul\Lead\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Contracts\PolicyServiceCase as PolicyServiceCaseContract;
use Webkul\User\Models\UserProxy;

class PolicyServiceCase extends Model implements PolicyServiceCaseContract
{
    protected $table = 'policy_service_cases';

    protected $fillable = [
        'ticket_number',
        'policy_id',
        'lead_id',
        'person_id',
        'user_id',
        'category',
        'priority',
        'status',
        'subject',
        'description',
        'resolution_notes',
        'due_date',
        'resolved_at',
        'closed_at',
        'attachment_path',
        'is_shared_with_client',
    ];

    protected $casts = [
        'due_date' => 'date',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_shared_with_client' => 'boolean',
    ];

    protected $appends = [
        'category_label',
        'status_label',
        'priority_label',
        'is_resolved',
    ];

    /**
     * Boot model to automatically generate ticket_number if not provided.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ticket_number)) {
                $year = date('Y');
                $random = strtoupper(Str::random(6));
                $model->ticket_number = "CAS-{$year}-{$random}";
            }
        });
    }

    /**
     * Parent insurance policy relationship.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'policy_id');
    }

    /**
     * Related lead relationship.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    /**
     * Related person contact relationship.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonProxy::modelClass(), 'person_id');
    }

    /**
     * Assigned agent relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'user_id');
    }

    /**
     * Case comments / notes relationship.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PolicyServiceCaseCommentProxy::modelClass(), 'service_case_id')->orderBy('created_at', 'asc');
    }

    /**
     * Check if case is resolved or closed.
     */
    public function getIsResolvedAttribute(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Human readable translated category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        $key = 'admin::insurance.service_cases.categories.'.$this->category;

        return trans()->has($key) ? trans($key) : ucfirst(str_replace('_', ' ', $this->category));
    }

    /**
     * Human readable translated status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $key = 'admin::insurance.service_cases.statuses.'.$this->status;

        return trans()->has($key) ? trans($key) : ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Human readable translated priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        $key = 'admin::insurance.service_cases.priorities.'.$this->priority;

        return trans()->has($key) ? trans($key) : ucfirst($this->priority);
    }
}
