<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    public const ACTIVE = 'active';

    public const PAUSED = 'paused';

    public const COMPLETED = 'completed';

    public const EXITED = 'exited';

    protected $table = 'communication_enrollments';

    protected $fillable = [
        'sequence_id', 'trigger_id', 'person_id', 'lead_id', 'policy_id', 'unique_key',
        'next_position', 'next_run_at', 'status', 'exit_reason', 'enrolled_by', 'finished_at',
    ];

    protected $casts = [
        'next_run_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(StepRun::class)->latest('id');
    }
}
