<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Lead\Contracts\Source as SourceContract;

class Source extends Model implements SourceContract
{
    protected $table = 'lead_sources';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Return translated name if available in active locale.
     */
    public function getNameAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $value), '_'));

        $insuranceKey = "admin::insurance.lead_sources.{$slug}";
        if (trans()->has($insuranceKey)) {
            return trans($insuranceKey);
        }

        $appKey = "admin::app.lead_sources.{$slug}";
        if (trans()->has($appKey)) {
            return trans($appKey);
        }

        return $value;
    }

    /**
     * Get raw database name.
     */
    public function getRoNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    /**
     * Get the leads.
     */
    public function leads()
    {
        return $this->hasMany(LeadProxy::modelClass(), 'lead_source_id', 'id');
    }
}
