<?php

namespace Webkul\Activity\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Activity\Contracts\Activity as ActivityContract;
use Webkul\Contact\Models\PersonProxy;
use Webkul\Lead\Models\LeadProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\User\Models\UserProxy;
use Webkul\Warehouse\Models\WarehouseProxy;

class Activity extends Model implements ActivityContract
{
    /**
     * Actionable activity types displayed on calendar and activity lists.
     */
    public const ACTIONABLE_TYPES = [
        'call',
        'meeting',
        'lunch',
        'consent_request',
        'soa',
        'docs_verification',
        'application_submit',
        'renewal_review',
    ];

    /**
     * Define table name of property
     *
     * @var string
     */
    protected $table = 'activities';

    /**
     * Define relationships that should be touched on save
     *
     * @var array
     */
    protected $with = ['user'];

    /**
     * Cast attributes to date time
     *
     * @var array
     */
    protected $casts = [
        'schedule_from' => 'datetime',
        'schedule_to' => 'datetime',
        'is_done' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'type',
        'location',
        'comment',
        'additional',
        'schedule_from',
        'schedule_to',
        'is_done',
        'user_id',
        'priority',
        'sla_activity_status',
    ];

    /**
     * Get translated activity type name.
     */
    public function getTypeNameAttribute(): string
    {
        $insuranceKey = "admin::insurance.activity_types.{$this->type}";
        if (trans()->has($insuranceKey)) {
            return trans($insuranceKey);
        }

        $appKey = "admin::app.activities.index.datagrid.{$this->type}";
        if (trans()->has($appKey)) {
            return trans($appKey);
        }

        return ucfirst(str_replace('_', ' ', $this->type ?? ''));
    }

    /**
     * Get a translated label for the priority level.
     */
    public function getPriorityLabelAttribute(): string
    {
        $key = 'admin::insurance.team_radar.priority_'.($this->priority ?? 'normal');

        return trans()->has($key) ? trans($key) : ucfirst($this->priority ?? 'normal');
    }

    /**
     * Scope: urgent activities flagged by the Master Agent.
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    /**
     * Scope: activities still pending (not done).
     */
    public function scopePending($query)
    {
        return $query->where('is_done', false);
    }

    /**
     * Get the user that owns the activity.
     */
    public function user()
    {
        return $this->belongsTo(UserProxy::modelClass());
    }

    /**
     * The participants that belong to the activity.
     */
    public function participants()
    {
        return $this->hasMany(ParticipantProxy::modelClass());
    }

    /**
     * Get the file associated with the activity.
     */
    public function files()
    {
        return $this->hasMany(FileProxy::modelClass(), 'activity_id');
    }

    /**
     * The leads that belong to the activity.
     */
    public function leads()
    {
        return $this->belongsToMany(LeadProxy::modelClass(), 'lead_activities');
    }

    /**
     * The Person that belong to the activity.
     */
    public function persons()
    {
        return $this->belongsToMany(PersonProxy::modelClass(), 'person_activities');
    }

    /**
     * The leads that belong to the activity.
     */
    public function products()
    {
        return $this->belongsToMany(ProductProxy::modelClass(), 'product_activities');
    }

    /**
     * The Warehouse that belong to the activity.
     */
    public function warehouses()
    {
        return $this->belongsToMany(WarehouseProxy::modelClass(), 'warehouse_activities');
    }
}
