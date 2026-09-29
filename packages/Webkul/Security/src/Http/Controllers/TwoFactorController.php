<?php

namespace Webkul\Security\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Security\Models\AccessLog;
use Webkul\Security\Repositories\AccessLogRepository;
use Webkul\Security\Services\TwoFactorManager;
use Webkul\Security\Support\Totp;

class TwoFactorController extends Controller
{
    const MAX_ATTEMPTS = 5;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TwoFactorManager $twoFactor,
        protected AccessLogRepository $accessLogRepository,
    ) {}

    /**
     * Code prompt shown right after the password step.
     */
    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $request->user('user');

        if (! $this->twoFactor->isEnabled($user) || $this->twoFactor->hasPassed($request, $user)) {
            return redirect()->intended(route('admin.dashboard.index'));
        }

        return view('security::two-factor.challenge', [
            'trustDays' => $this->twoFactor->trustDays(),
        ]);
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:20']]);

        $user = $request->user('user');

        $throttleKey = 'two-factor|'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return back()->withErrors([
                'code' => trans('security::app.two-factor.challenge.throttled', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        if (! $this->twoFactor->verify($user, $request->input('code'))) {
            RateLimiter::hit($throttleKey, 60);

            $this->accessLogRepository->record(AccessLog::EVENT_MFA_FAILED, $user->id, $user->email);

            return back()->withErrors(['code' => trans('security::app.two-factor.challenge.invalid')]);
        }

        RateLimiter::clear($throttleKey);

        $this->twoFactor->markPassed($request, $user);

        if ($request->boolean('trust_device') && $this->twoFactor->trustDays() > 0) {
            $this->twoFactor->trustDevice($request, $user);
        }

        return redirect()->intended(route('admin.dashboard.index'));
    }

    /**
     * "Account security" page: enrol, recovery codes, trusted devices.
     */
    public function setup(Request $request): View
    {
        $user = $request->user('user');

        $enabled = $this->twoFactor->isEnabled($user);

        $data = [
            'enabled' => $enabled,
            'required' => $this->twoFactor->isRequired($user),
            'recoveryCodes' => session('security.recovery_codes'),
            'remainingCodes' => $enabled ? $this->twoFactor->remainingRecoveryCodes($user) : 0,
            'devices' => $enabled ? $this->twoFactor->trustedDevices($user) : collect(),
        ];

        if (! $enabled) {
            $secret = $this->twoFactor->pendingSecret($request);

            $data['secret'] = Totp::formatSecret($secret);
            $data['otpauthUri'] = Totp::provisioningUri($secret, $user->email, config('app.name', 'CRM'));
        }

        return view('security::two-factor.setup', $data);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:20']]);

        $user = $request->user('user');

        if (! $codes = $this->twoFactor->confirm($request, $user, $request->input('code'))) {
            return back()->withErrors(['code' => trans('security::app.two-factor.challenge.invalid')]);
        }

        $this->twoFactor->markPassed($request, $user);

        $this->accessLogRepository->record(AccessLog::EVENT_MFA_ENABLED, $user->id, $user->email);

        session()->flash('success', trans('security::app.two-factor.setup.enabled-success'));

        return redirect()->route('admin.security.two_factor.setup')->with('security.recovery_codes', $codes);
    }

    public function regenerateCodes(Request $request): RedirectResponse
    {
        $user = $request->user('user');

        if (! $this->verifyCurrentCode($request, $user)) {
            return back()->withErrors(['code' => trans('security::app.two-factor.challenge.invalid')]);
        }

        $codes = $this->twoFactor->regenerateRecoveryCodes($user);

        return redirect()->route('admin.security.two_factor.setup')->with('security.recovery_codes', $codes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user('user');

        if ($this->twoFactor->isRequired($user)) {
            return back()->withErrors(['code' => trans('security::app.two-factor.setup.cannot-disable')]);
        }

        if (! $this->verifyCurrentCode($request, $user)) {
            return back()->withErrors(['code' => trans('security::app.two-factor.challenge.invalid')]);
        }

        $this->twoFactor->disable($user);

        $this->accessLogRepository->record(AccessLog::EVENT_MFA_DISABLED, $user->id, $user->email);

        session()->flash('success', trans('security::app.two-factor.setup.disabled-success'));

        return redirect()->route('admin.security.two_factor.setup');
    }

    public function forgetDevices(Request $request): RedirectResponse
    {
        $this->twoFactor->forgetDevices($request->user('user'));

        session()->flash('success', trans('security::app.two-factor.setup.devices-forgotten'));

        return redirect()->route('admin.security.two_factor.setup');
    }

    protected function verifyCurrentCode(Request $request, $user): bool
    {
        $request->validate(['code' => ['required', 'string', 'max:20']]);

        return $this->twoFactor->verify($user, $request->input('code'));
    }
}
