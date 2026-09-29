<?php

namespace Webkul\Teamwork\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;

class OverdueRule extends Model
{
    protected $table = 'teamwork_overdue_rules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'agency_id',
        'lead_pipeline_id',
        'lead_pipeline_stage_id',
        'warning_hours',
        'overdue_hours',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class, 'lead_pipeline_id');
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class, 'lead_pipeline_stage_id');
    }
}
