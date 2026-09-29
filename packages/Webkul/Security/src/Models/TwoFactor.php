<?php

namespace Webkul\Security\Models;

use Illuminate\Database\Eloquent\Model;

class TwoFactor extends Model
{
    protected $table = 'user_two_factor';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'secret',
        'recovery_codes',
        'last_used_step',
        'confirmed_at',
    ];

    /**
     * Secrets and recovery codes are encrypted at rest with the app key.
     */
    protected $casts = [
        'secret' => 'encrypted',
        'recovery_codes' => 'encrypted:array',
        'confirmed_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
        'recovery_codes',
    ];
}
