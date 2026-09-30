<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class ContactConsent extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'contact_consents';

    protected $fillable = [
        'person_id',
        'channel',
        'status',
        'source',
        'note',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
