<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trigger extends Model
{
    /**
     * What can start a sequence. Stage events are set per stage in
     * Settings; the rest in the sequence itself.
     */
    public const EVENTS = [
        'stage_entered',      // config: stage_id
        'stage_idle',         // config: stage_id, days
        'policy_effectuated', // —
        'policy_status',      // config: status
        'call_outcome',       // config: outcome
        'birthday',           // —
        'renewal_before',     // config: days
        'manual',             // —
    ];

    public const STAGE_EVENTS = ['stage_entered', 'stage_idle'];

    protected $table = 'communication_triggers';

    protected $fillable = ['sequence_id', 'event', 'config', 'is_active'];

    protected $casts = ['config' => 'array', 'is_active' => 'boolean'];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function option(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }
}
