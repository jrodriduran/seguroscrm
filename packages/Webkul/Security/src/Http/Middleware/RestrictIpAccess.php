<?php

namespace Webkul\Security\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Security\Models\AccessLog;
use Webkul\Security\Repositories\AccessLogRepository;
use Webkul\User\Models\User;

/**
 * Limits the admin panel to an allowlist of IP addresses / CIDR ranges.
 *
 * It only acts on staff: signed-in users and the sign-in form itself.
 * Public endpoints under the admin path (web forms, consent portal…) are
 * untouched. Loopback is always allowed, and administrators can be exempted
 * so a bad allowlist never locks everyone out.
 */
class RestrictIpAccess
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(protected AccessLogRepository $accessLogRepository) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEnabled() || $this->isAllowed($request->ip())) {
            return $next($request);
        }

        $guard = auth()->guard('user');

        if ($guard->check()) {
            if ($this->isExempt($guard->user())) {
                return $next($request);
            }

            $this->accessLogRepository->record(AccessLog::EVENT_BLOCKED_IP, $guard->user()->id, $guard->user()->email, $request);

            $guard->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->deny($request);
        }

        if ($request->isMethod('post') && $request->routeIs('admin.session.store')) {
            $user = User::where('email', $request->input('email'))->first();

            if (! $user || ! $this->isExempt($user)) {
                $this->accessLogRepository->record(AccessLog::EVENT_BLOCKED_IP, $user?->id, $request->input('email'), $request);

                return $this->deny($request);
            }
        }

        return $next($request);
    }

    /**
     * Whether the allowlist is switched on and has at least one entry.
     */
    protected function isEnabled(): bool
    {
        return (bool) core()->getConfigData('general.security.ip_restriction.enabled')
            && ! empty($this->allowlist());
    }

    /**
     * Whether the IP matches loopback or any allowlist entry.
     */
    protected function isAllowed(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }

        return IpUtils::checkIp($ip, array_merge(['127.0.0.1', '::1'], $this->allowlist()));
    }

    /**
     * Administrators (full permission roles) can be exempted.
     */
    protected function isExempt($user): bool
    {
        return core()->getConfigData('general.security.ip_restriction.exempt_admins') !== '0'
            && $user->role?->permission_type === 'all';
    }

    /**
     * Allowlist entries from configuration, one per comma, space or line.
     */
    protected function allowlist(): array
    {
        $raw = (string) core()->getConfigData('general.security.ip_restriction.allowed_ips');

        return array_values(array_filter(preg_split('/[\s,;]+/', $raw)));
    }

    /**
     * Send the visitor back to the sign-in page with an explanation.
     */
    protected function deny(Request $request): Response
    {
        $message = trans('security::app.ip-restriction.denied');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        session()->flash('error', $message);

        return redirect()->route('admin.session.create');
    }
}
