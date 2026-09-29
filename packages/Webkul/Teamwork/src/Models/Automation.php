<?php

namespace Webkul\Teamwork\Models;

use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    const TRIGGERS = ['lead_created', 'stage_entered', 'renewal_upcoming', 'case_overdue'];

    const ACTIONS = ['follow_up', 'notify'];

    const ASSIGNEES = ['owner', 'master', 'user'];

    protected $table = 'teamwork_automations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'agency_id',
        'name',
        'trigger',
        'conditions',
        'action',
        'assign_to',
        'assign_user_id',
        'priority',
        'due_in_days',
        'note_template',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function runs()
    {
        return $this->hasMany(AutomationRun::class);
    }

    public function condition(string $key, $default = null)
    {
        return data_get($this->conditions, $key, $default);
    }
}
