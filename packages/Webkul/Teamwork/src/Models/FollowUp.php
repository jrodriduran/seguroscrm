<?php

namespace Webkul\Teamwork\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\UserProxy;

class FollowUp extends Model
{
    const STATUS_OPEN = 'open';

    const STATUS_DONE = 'done';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_URGENT = 'urgent';

    protected $table = 'teamwork_follow_ups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'agency_id',
        'entity_type',
        'entity_id',
        'title',
        'url',
        'assigned_to',
        'created_by',
        'priority',
        'note',
        'due_at',
        'status',
        'resolved_at',
        'resolved_by',
        'last_activity_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function assignee()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'created_by');
    }

    public function resolver()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'resolved_by');
    }

    public function comments()
    {
        return $this->hasMany(FollowUpComment::class)->oldest('id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isUrgent(): bool
    {
        return $this->priority === self::PRIORITY_URGENT;
    }
}
