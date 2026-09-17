<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Webkul\Attribute\Contracts\AttributeOption as AttributeOptionContract;

class AttributeOption extends Model implements AttributeOptionContract
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'sort_order',
        'attribute_id',
    ];

    /**
     * Get translated option name according to the active interface locale.
     */
    public function getNameAttribute($value)
    {
        $attr = $this->attribute;
        if ($attr && $attr->code) {
            $raw = $this->getRawOriginal('name') ?? $value;
            $slug = Str::slug($raw, '_');

            $candidates = [
                "admin::insurance.attribute_options.{$attr->code}.{$slug}",
                "admin::app.attribute_options.{$attr->code}.{$slug}",
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
     * Get the attribute that owns the attribute option.
     */
    public function attribute()
    {
        return $this->belongsTo(AttributeProxy::modelClass());
    }
}
