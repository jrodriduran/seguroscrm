<?php

namespace Webkul\Teamwork\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\UserProxy;

class Notification extends Model
{
    const UPDATED_AT = null;

    protected $table = 'teamwork_notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'actor_id',
        'type',
        'title',
        'body',
        'url',
        'is_urgent',
        'read_at',
    ];

    protected $casts = [
        'is_urgent' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'actor_id');
    }
}
