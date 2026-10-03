<?php

namespace Webkul\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Platform\Services\PlatformState;

/**
 * Applies the subscription state on admin pages: "read only" blocks every
 * change, "suspended" shows a contact-support page. Signing in and out
 * always works, and no data is ever deleted.
 */
class EnforceSubscription
{
    protected const ALWAYS = ['admin.session.*', 'admin.forgot_password.*', 'admin.reset_password.*', 'admin.security.two_factor.*', 'platform.*'];

    public function __construct(protected PlatformState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        $status = $this->state->status();

        if ($status === 'active' || $status === 'warning' || ! $request->is(trim(config('app.admin_path'), '/').'*') || $request->routeIs(...self::ALWAYS)) {
            return $next($request);
        }

        if ($status === 'suspended') {
            if ($request->expectsJson()) {
                return response()->json(['message' => trans('platform::app.suspended.title')], 402);
            }

            return response()->view('platform::suspended', ['message' => $this->state->message()], 402);
        }

        // Read only: looking and exporting are fine; anything that changes data is not.
        if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => trans('platform::app.read-only.blocked')], 423);
            }

            session()->flash('error', trans('platform::app.read-only.blocked'));

            return redirect()->back();
        }

        return $next($request);
    }
}
