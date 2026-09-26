<?php

namespace Webkul\Lead\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
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
        'date_of_birth' => 'date',
        'is_applying_coverage' => 'boolean',
        'tobacco_user' => 'boolean',
    ];

    protected $appends = [
        'age',
        'relationship_label',
        'masked_ssn',
    ];

    /**
     * Mutator to encrypt SSN / ITIN at application layer for HIPAA/PII compliance.
     */
    public function setSsnItinAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['ssn_itin'] = null;

            return;
        }

        $trimmed = trim((string) $value);
        $this->attributes['ssn_itin'] = Crypt::encryptString($trimmed);
    }

    /**
     * Accessor to transparently decrypt SSN / ITIN (with graceful fallback for legacy plaintext).
     */
    public function getSsnItinAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    /**
     * Return masked SSN (e.g. ***-**-1234) for secure UI rendering without leaking PII.
     */
    public function getMaskedSsnAttribute(): ?string
    {
        $raw = $this->ssn_itin;
        if (empty($raw)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw);
        if (strlen($digits) >= 4) {
            return '***-**-'.substr($digits, -4);
        }

        return '***-**-****';
    }

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
        $key = 'admin::insurance.household.relationships.'.$this->relationship;

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
