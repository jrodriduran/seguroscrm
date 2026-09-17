<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Webkul\Lead\Contracts\Pipeline as PipelineContract;

class Pipeline extends Model implements PipelineContract
{
    protected $table = 'lead_pipelines';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'rotten_days',
        'is_default',
    ];

    /**
     * Get translated name according to the active interface locale.
     */
    public function getNameAttribute($value)
    {
        $raw = $this->getRawOriginal('name') ?? $value;
        $slug = Str::slug($raw, '_');

        $candidates = [
            "admin::insurance.pipelines.{$slug}",
            "admin::app.pipelines.{$slug}",
        ];

        foreach ($candidates as $key) {
            if (Lang::has($key)) {
                return trans($key);
            }
        }

        return $value;
    }

    /**
     * Get the leads.
     */
    public function leads()
    {
        return $this->hasMany(LeadProxy::modelClass(), 'lead_pipeline_id');
    }

    /**
     * Get the stages that owns the pipeline.
     */
    public function stages()
    {
        return $this->hasMany(StageProxy::modelClass(), 'lead_pipeline_id')->orderBy('sort_order', 'ASC');
    }
}
