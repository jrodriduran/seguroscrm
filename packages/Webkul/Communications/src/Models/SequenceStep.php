<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SequenceStep extends Model
{
    /**
     * Messages go out through a channel; the rest are actions for the team.
     */
    public const MESSAGE_TYPES = ['preferred', 'email', 'whatsapp', 'sms'];

    public const ACTION_TYPES = ['call_task', 'notify', 'physical', 'tag'];

    public const CONDITIONS = ['always', 'if_no_reply'];

    protected $table = 'communication_sequence_steps';

    protected $fillable = ['sequence_id', 'position', 'type', 'delay_days', 'send_hour', 'template_id', 'condition', 'config'];

    protected $casts = ['config' => 'array'];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    public function isMessage(): bool
    {
        return in_array($this->type, self::MESSAGE_TYPES, true);
    }

    public function option(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }
}
