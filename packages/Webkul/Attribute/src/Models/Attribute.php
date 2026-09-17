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
        'is_required',
        'is_unique',
        'validation',
        'sort_order',
        'entity_type',
        'lookup_type',
        'quick_add',
        'is_user_defined',
    ];

    /**
     * Get translated name according to the active interface locale.
     */
    public function getNameAttribute($value)
    {
        if (!empty($this->entity_type) && !empty($this->code)) {
            $candidates = [
                "admin::insurance.attributes.{$this->entity_type}.{$this->code}",
                "admin::app.attributes.{$this->entity_type}.{$this->code}",
            ];

            foreach ($candidates as $key) {
                if (Lang::has($key)) {
                    return trans($key);
                }
            }
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
