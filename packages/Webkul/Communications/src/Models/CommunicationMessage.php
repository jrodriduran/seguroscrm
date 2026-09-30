<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class CommunicationMessage extends Model
{
    protected $table = 'communication_messages';

    protected $fillable = [
        'provider',
        'external_id',
        'conversation_id',
        'inbox_id',
        'channel',
        'direction',
        'person_id',
        'lead_id',
        'user_id',
        'sender_name',
        'content',
        'attachments',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
