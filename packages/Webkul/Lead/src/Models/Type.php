<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Webkul\Lead\Contracts\Type as TypeContract;

class Type extends Model implements TypeContract
{
    protected $table = 'lead_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get translated name according to the active interface locale.
     */
    public function getNameAttribute($value)
    {
        $raw = $this->getRawOriginal('name') ?? $value;
        $slug = Str::slug($raw, '_');
        $key = "admin::app.lead_types.{$slug}";

        if (Lang::has($key)) {
            return trans($key);
        }

        return $value;
    }

    /**
     * Get translated description according to the active interface locale.
     */
    public function getDescriptionAttribute($value)
    {
        $raw = $this->getRawOriginal('name') ?? '';
        $slug = Str::slug($raw, '_');
        $key = "admin::app.lead_types.{$slug}_description";

        if (Lang::has($key)) {
            return trans($key);
        }

        return $value;
    }

    /**
     * Get the leads.
     */
    public function leads()
    {
        return $this->hasMany(LeadProxy::modelClass());
    }
}
