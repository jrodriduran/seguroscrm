<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StepRun extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'communication_step_runs';

    protected $fillable = ['enrollment_id', 'step_id', 'status', 'channel', 'detail', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function step(): BelongsTo
    {
        return $this->belongsTo(SequenceStep::class, 'step_id');
    }
}
