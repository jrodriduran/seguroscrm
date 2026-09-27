<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Lead\Contracts\Agency as AgencyContract;
use Webkul\User\Models\User;

class Agency extends Model implements AgencyContract
{
    protected $table = 'agencies';

    protected $fillable = [
        'name',
        'code',
        'ein_tax_id',
        'email',
        'phone',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }
}
