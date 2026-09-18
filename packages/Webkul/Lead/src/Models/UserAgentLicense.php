<?php

namespace Webkul\Lead\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\UserAgentLicense as UserAgentLicenseContract;
use Webkul\User\Models\User;

class UserAgentLicense extends Model implements UserAgentLicenseContract
{
    protected $table = 'user_agent_licenses';

    protected $fillable = [
        'user_id',
        'state_code',
        'license_number',
        'license_type',
        'lines_of_authority',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'lines_of_authority' => 'array',
        'expires_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDaysUntilExpirationAttribute(): int
    {
        if (! $this->expires_at) {
            return 0;
        }

        return (int) Carbon::today()->diffInDays($this->expires_at, false);
    }

    public function isExpired(): bool
    {
        return $this->days_until_expiration < 0;
    }

    public function isExpiringSoon(): bool
    {
        return $this->days_until_expiration >= 0 && $this->days_until_expiration <= 30;
    }
}
