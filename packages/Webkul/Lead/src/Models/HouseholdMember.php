<?php

namespace Webkul\Lead\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Webkul\Lead\Contracts\HouseholdMember as HouseholdMemberContract;

class HouseholdMember extends Model implements HouseholdMemberContract
{
    protected $table = 'lead_household_members';

    protected $fillable = [
        'lead_id',
        'name',
        'relationship',
        'date_of_birth',
        'gender',
        'ssn_itin',
        'immigration_status',
        'is_applying_coverage',
        'tobacco_user',
        'notes',
    ];

    protected $casts = [
        'date_of_birth'        => 'date',
        'is_applying_coverage' => 'boolean',
        'tobacco_user'         => 'boolean',
    ];

    protected $appends = [
        'age',
        'relationship_label',
    ];

    /**
     * Get age based on DOB.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return Carbon::parse($this->date_of_birth)->age;
    }

    /**
     * Get translated relationship label.
     */
    public function getRelationshipLabelAttribute(): string
    {
        $key = 'admin::insurance.household.relationships.' . $this->relationship;

        return trans()->has($key) ? trans($key) : ucfirst($this->relationship);
    }

    /**
     * Get the lead that owns the household member.
     */
    public function lead()
    {
        return $this->belongsTo(LeadProxy::modelClass());
    }
}