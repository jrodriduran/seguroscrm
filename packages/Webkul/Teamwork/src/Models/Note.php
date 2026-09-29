<?php

namespace Webkul\Teamwork\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\UserProxy;

class Note extends Model
{
    protected $table = 'teamwork_notes';

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
        'from_user_id',
        'to_user_id',
        'body',
        'reply_to_id',
        'read_at',
        'follow_up_id',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'from_user_id');
    }

    public function recipient()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'to_user_id');
    }

    public function followUp()
    {
        return $this->belongsTo(FollowUp::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function isRead(): bool
    {
        return (bool) $this->read_at;
    }
}
