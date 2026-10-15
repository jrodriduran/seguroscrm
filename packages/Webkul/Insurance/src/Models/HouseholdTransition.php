<?php

namespace Webkul\Insurance\Models;

use Illuminate\Database\Eloquent\Model;

class HouseholdTransition extends Model
{
    protected $table = 'insurance_household_transitions';

    protected $guarded = ['id'];

    protected $casts = [
        'coverage_end_date' => 'date:Y-m-d',
        'review_on' => 'date:Y-m-d',
        'resolved_at' => 'datetime',
    ];
}
