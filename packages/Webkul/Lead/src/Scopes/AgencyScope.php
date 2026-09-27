<?php

namespace Webkul\Lead\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AgencyScope implements Scope
{
    /**
     * Apply the agency tenant filter to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $user = auth()->user();

            if (! empty($user->agency_id)) {
                $builder->where($model->getTable().'.agency_id', $user->agency_id);
            }
        }
    }
}
