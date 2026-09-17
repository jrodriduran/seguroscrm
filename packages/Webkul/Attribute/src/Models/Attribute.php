<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Webkul\Attribute\Contracts\Attribute as AttributeContract;

class Attribute extends Model implements AttributeContract
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'entity_type',
        'lookup_type',
        'is_required',
        'is_unique',
        'quick_add',
        'validation',
        'is_user_defined',
    ];

    /**
     * Get translated name according to the active interface locale.
     */
    public function getNameAttribute($value)
    {
        $key = "admin::app.attributes.{$this->entity_type}.{$this->code}";

        if (Lang::has($key)) {
            return trans($key);
        }

        return $value;
    }

    /**
     * Get the options.
     */
    public function options()
    {
        return $this->hasMany(AttributeOptionProxy::modelClass());
    }
}
