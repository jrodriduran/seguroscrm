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
        $code = $this->code;
        $codeSlug = Str::slug($code ?? '', '_');
        $nameSlug = Str::slug($this->getRawOriginal('name') ?? $value, '_');
        $pipeline = $this->pipeline;
        $pipeSlug = $pipeline ? Str::slug($pipeline->getRawOriginal('name'), '_') : '';

        $candidates = [
            // Look by pipeline + code
            !empty($pipeSlug) && !empty($codeSlug) ? "admin::insurance.pipeline_stages.{$pipeSlug}.{$codeSlug}" : null,
            // Look by general code
            !empty($codeSlug) ? "admin::insurance.pipeline_stages.general.{$codeSlug}" : null,
            // Look by name slug
            !empty($nameSlug) ? "admin::insurance.pipeline_stages.general.{$nameSlug}" : null,
            // Fallbacks in app
            !empty($pipeSlug) && !empty($codeSlug) ? "admin::app.pipeline_stages.{$pipeSlug}.{$codeSlug}" : null,
            !empty($codeSlug) ? "admin::app.pipeline_stages.general.{$codeSlug}" : null,
            !empty($nameSlug) ? "admin::app.pipeline_stages.general.{$nameSlug}" : null,
        ];

        foreach ($candidates as $key) {
            if (!empty($key) && Lang::has($key)) {
                return trans($key);
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
