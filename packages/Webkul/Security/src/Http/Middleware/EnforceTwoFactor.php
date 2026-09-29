<?php

namespace Webkul\Security\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Security\Services\TwoFactorManager;

/**
 * After the password step, a signed-in user with two-factor enabled must
 * pass the code challenge (or come from a trusted browser) before reaching
 * any admin page. Users the policy obliges to enrol are sent to setup.
 */
class EnforceTwoFactor
{
    /**
     * Routes reachable while the challenge is still pending.
     */
    protected array $except = [
        'admin.security.two_factor.*',
        'admin.session.destroy',
    ];

    /**
     * Create a new middleware instance.
     */
    public function __construct(protected TwoFactorManager $twoFactor) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->guard('user')->user();

        if (
            ! $user
            || ! $request->is(trim(config('app.admin_path'), '/').'*')
            || $request->routeIs(...$this->except)
            || $this->twoFactor->hasPassed($request, $user)
        ) {
            return $next($request);
        }

        if ($this->twoFactor->isEnabled($user)) {
            if ($this->twoFactor->isTrustedDevice($request, $user)) {
                $this->twoFactor->markPassed($request, $user);

                return $next($request);
            }

            return $this->redirect($request, 'admin.security.two_factor.challenge');
        }

        if ($this->twoFactor->isRequired($user)) {
            return $this->redirect($request, 'admin.security.two_factor.setup');
        }

        return $next($request);
    }

    protected function redirect(Request $request, string $route): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => trans('security::app.two-factor.challenge.required'), 'redirect' => route($route)], 401);
        }

        if ($request->isMethod('get')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route($route);
    }
}
