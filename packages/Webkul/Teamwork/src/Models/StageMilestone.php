<?php

namespace Webkul\Teamwork\Models;

use Illuminate\Database\Eloquent\Model;

class StageMilestone extends Model
{
    protected $table = 'teamwork_stage_milestones';

    protected $fillable = ['lead_pipeline_stage_id', 'position', 'name', 'check', 'is_required', 'help'];

    protected $casts = ['is_required' => 'boolean'];

    public function isAutomatic(): bool
    {
        return (bool) $this->check;
    }
}
