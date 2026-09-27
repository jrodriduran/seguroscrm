<?php

namespace Webkul\Lead\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Models\Agency;
use Webkul\Lead\Scopes\AgencyScope;

trait BelongsToAgency
{
    /**
     * Boot the agency multi-tenancy trait for a model.
     */
    public static function bootBelongsToAgency(): void
    {
        static::addGlobalScope(new AgencyScope);

        static::creating(function ($model) {
            if (auth()->check() && empty($model->agency_id)) {
                $user = auth()->user();

                if (! empty($user->agency_id)) {
                    $model->agency_id = $user->agency_id;
                }
            }
        });
    }

    /**
     * Get the agency that owns this record.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
