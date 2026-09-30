<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sequence extends Model
{
    protected $table = 'communication_sequences';

    protected $fillable = ['agency_id', 'code', 'name', 'description', 'pause_on_reply', 'exit_on_stage_change', 'allow_reentry', 'is_active'];

    protected $casts = [
        'pause_on_reply' => 'boolean',
        'exit_on_stage_change' => 'boolean',
        'allow_reentry' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(SequenceStep::class)->orderBy('position');
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(Trigger::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
