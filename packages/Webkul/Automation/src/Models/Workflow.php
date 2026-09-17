<?php

namespace Webkul\Automation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Webkul\Automation\Contracts\Workflow as WorkflowContract;

class Workflow extends Model implements WorkflowContract
{
    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
    ];

    protected $fillable = [
        'name',
        'description',
        'entity_type',
        'event',
        'condition_type',
        'conditions',
        'actions',
    ];

    /**
     * Get the workflow name translated dynamically if defined.
     */
    public function getNameAttribute($value)
    {
        $raw = $this->getRawOriginal('name') ?? $value;
        $slug = Str::slug($raw ?? '', '_');

        $candidates = [
            "admin::insurance.workflows.{$slug}",
            "admin::app.settings.workflows.{$slug}",
        ];

        foreach ($candidates as $candidate) {
            if (Lang::has($candidate)) {
                return trans($candidate);
            }
        }

        return $value;
    }

    /**
     * Get the workflow description translated dynamically if defined.
     */
    public function getDescriptionAttribute($value)
    {
        $rawName = $this->getRawOriginal('name') ?? '';
        $slug = Str::slug($rawName, '_');

        $candidates = [
            "admin::insurance.workflows.{$slug}_description",
            "admin::app.settings.workflows.{$slug}_description",
        ];

        foreach ($candidates as $candidate) {
            if (Lang::has($candidate)) {
                return trans($candidate);
            }
        }

        return $value;
    }
}
