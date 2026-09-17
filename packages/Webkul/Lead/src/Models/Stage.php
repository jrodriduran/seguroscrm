<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Webkul\Lead\Contracts\Stage as StageContract;

class Stage extends Model implements StageContract
{
    public $timestamps = false;

    protected $table = 'lead_pipeline_stages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'probability',
        'sort_order',
        'lead_pipeline_id',
    ];

    /**
     * Get translated stage name according to the active interface locale.
     */
    public function getNameAttribute($value)
    {
        if ($this->code) {
            $pipeline = $this->pipeline;
            $pipelineSlug = $pipeline ? Str::slug($pipeline->getRawOriginal('name'), '_') : 'default';
            $key = "admin::app.pipeline_stages.{$pipelineSlug}.{$this->code}";

            if (Lang::has($key)) {
                return trans($key);
            }

            $generalKey = "admin::app.pipeline_stages.general.{$this->code}";
            if (Lang::has($generalKey)) {
                return trans($generalKey);
            }
        }

        return $value;
    }

    /**
     * Get the pipeline that owns the pipeline stage.
     */
    public function pipeline()
    {
        return $this->belongsTo(PipelineProxy::modelClass(), 'lead_pipeline_id');
    }

    /**
     * Get the leads.
     */
    public function leads()
    {
        return $this->hasMany(LeadProxy::modelClass(), 'lead_pipeline_stage_id');
    }
}
