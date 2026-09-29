<?php

namespace Webkul\Security\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Webkul\Security\Models\TrustedDevice;
use Webkul\Security\Models\TwoFactor;
use Webkul\Security\Support\Totp;

class TwoFactorManager
{
    /**
     * Session key holding the id of the user who passed the challenge.
     */
    const SESSION_PASSED = 'security.two_factor.passed';

    /**
     * Session key holding a secret that is shown but not yet confirmed.
     */
    const SESSION_PENDING = 'security.two_factor.pending_secret';

    const TRUSTED_COOKIE = 'crm_trusted_device';

    const RECOVERY_CODES = 8;

    /**
     * Confirmed two-factor record for a user, if any.
     */
    public function forUser($user): ?TwoFactor
    {
        return TwoFactor::where('user_id', $user->id)->whereNotNull('confirmed_at')->first();
    }

    public function isEnabled($user): bool
    {
        return (bool) $this->forUser($user);
    }

    /**
     * Whether the security policy forces this user to enrol.
     */
    public function isRequired($user): bool
    {
        return match (core()->getConfigData('general.security.two_factor.required_for')) {
            'all' => true,
            'admins' => $user->role?->permission_type === 'all',
            default => false,
        };
    }

    public function hasPassed(Request $request, $user): bool
    {
        return $request->session()->get(self::SESSION_PASSED) === $user->id;
    }

    public function markPassed(Request $request, $user): void
    {
        $request->session()->put(self::SESSION_PASSED, $user->id);
    }

    /**
     * Secret shown during setup, generated once per session.
     */
    public function pendingSecret(Request $request): string
    {
        if (! $secret = $request->session()->get(self::SESSION_PENDING)) {
            $secret = Totp::generateSecret();

            $request->session()->put(self::SESSION_PENDING, $secret);
        }

        return $secret;
    }

    /**
     * Confirm setup with a code from the app. Returns fresh recovery codes, or null if the code is wrong.
     */
    public function confirm(Request $request, $user, string $code): ?array
    {
        $secret = $this->pendingSecret($request);

        if (! $step = Totp::verify($secret, $code)) {
            return null;
        }

        $codes = $this->generateRecoveryCodes();

        TwoFactor::updateOrCreate(['user_id' => $user->id], [
            'secret' => $secret,
            'recovery_codes' => $codes,
            'last_used_step' => $step,
            'confirmed_at' => now(),
        ]);

        $request->session()->forget(self::SESSION_PENDING);

        return $codes;
    }

    /**
     * Check a 6-digit app code or a recovery code (single use).
     */
    public function verify($user, string $code): bool
    {
        if (! $record = $this->forUser($user)) {
            return false;
        }

        $code = trim($code);

        if (preg_match('/^\d{3}\s?\d{3}$/', $code)) {
            $step = Totp::verify($record->secret, $code, $record->last_used_step);

            if (! $step) {
                return false;
            }

            $record->update(['last_used_step' => $step]);

            return true;
        }

        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));

        $codes = collect($record->recovery_codes ?? []);

        $match = $codes->first(fn ($candidate) => hash_equals(str_replace('-', '', $candidate), $normalized));

        if (! $match) {
            return false;
        }

        $record->update(['recovery_codes' => $codes->reject(fn ($candidate) => $candidate === $match)->values()->all()]);

        return true;
    }

    public function regenerateRecoveryCodes($user): array
    {
        $codes = $this->generateRecoveryCodes();

        $this->forUser($user)?->update(['recovery_codes' => $codes]);

        return $codes;
    }

    public function remainingRecoveryCodes($user): int
    {
        return count($this->forUser($user)?->recovery_codes ?? []);
    }

    /**
     * Turn two-factor off and forget every trusted device.
     */
    public function disable($user): void
    {
        TwoFactor::where('user_id', $user->id)->delete();

        $this->forgetDevices($user);
    }

    /**
     * Remember this browser so it skips the challenge for the configured number of days.
     */
    public function trustDevice(Request $request, $user): void
    {
        $token = Str::random(64);

        $days = $this->trustDays();

        TrustedDevice::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'last_used_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);

        Cookie::queue(Cookie::make(self::TRUSTED_COOKIE, $token, $days * 24 * 60, null, null, $request->isSecure(), true, false, 'lax'));
    }

    /**
     * Whether the request comes from a browser this user trusted.
     */
    public function isTrustedDevice(Request $request, $user): bool
    {
        if ($this->trustDays() <= 0 || ! $token = $request->cookie(self::TRUSTED_COOKIE)) {
            return false;
        }

        $device = TrustedDevice::where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        $device?->update(['last_used_at' => now()]);

        return (bool) $device;
    }

    public function trustedDevices($user)
    {
        return TrustedDevice::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest('last_used_at')
            ->get();
    }

    public function forgetDevices($user): void
    {
        TrustedDevice::where('user_id', $user->id)->delete();

        Cookie::queue(Cookie::forget(self::TRUSTED_COOKIE));
    }

    public function trustDays(): int
    {
        $days = core()->getConfigData('general.security.two_factor.trust_days');

        return $days === null || $days === '' ? 30 : max(0, (int) $days);
    }

    /**
     * Eight codes like "K7QX-M2PD".
     */
    protected function generateRecoveryCodes(): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return collect(range(1, self::RECOVERY_CODES))->map(function () use ($alphabet) {
            $code = '';

            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            return substr($code, 0, 4).'-'.substr($code, 4);
        })->all();
    }
}
