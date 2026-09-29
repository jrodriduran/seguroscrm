<?php

namespace Webkul\Security\Models;

use Illuminate\Database\Eloquent\Model;

class TrustedDevice extends Model
{
    protected $table = 'user_trusted_devices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'token_hash',
        'ip_address',
        'user_agent',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
