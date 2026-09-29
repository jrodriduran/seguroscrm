<?php

namespace Webkul\Teamwork\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\UserProxy;

class FollowUpComment extends Model
{
    const UPDATED_AT = null;

    protected $table = 'teamwork_follow_up_comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'follow_up_id',
        'user_id',
        'body',
    ];

    public function user()
    {
        return $this->belongsTo(UserProxy::modelClass());
    }
}
