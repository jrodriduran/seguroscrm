<?php

namespace Webkul\Teamwork\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StagePlaybook extends Model
{
    /**
     * off: no checks · warn: say what is missing · block: required
     * milestones must be done to move forward (the agency owner may override).
     */
    public const GATES = ['off', 'warn', 'block'];

    protected $table = 'teamwork_stage_playbooks';

    protected $fillable = [
        'lead_pipeline_stage_id', 'gate', 'entry_task', 'entry_task_title', 'entry_task_hours',
        'entry_assign', 'entry_user_id', 'notify_owner', 'notify_master', 'carry_over', 'close_previous',
    ];

    protected $casts = [
        'entry_task' => 'boolean',
        'notify_owner' => 'boolean',
        'notify_master' => 'boolean',
        'carry_over' => 'boolean',
        'close_previous' => 'boolean',
    ];

    public function milestones(): HasMany
    {
        return $this->hasMany(StageMilestone::class, 'lead_pipeline_stage_id', 'lead_pipeline_stage_id')->orderBy('position');
    }

    /**
     * The playbook of a stage, with defaults when none was saved.
     */
    public static function forStage(int $stageId): self
    {
        return static::firstOrNew(['lead_pipeline_stage_id' => $stageId], ['gate' => 'warn', 'notify_owner' => true, 'carry_over' => true, 'close_previous' => true, 'entry_task_hours' => 24, 'entry_assign' => 'owner']);
    }
}
