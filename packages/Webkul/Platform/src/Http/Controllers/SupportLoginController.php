<?php

namespace Webkul\Platform\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Platform\Services\SupportAccess;

/**
 * Opens a one-time support link: signs in as that user, marks the session
 * as a support session (banner), and records it in the access log.
 */
class SupportLoginController extends Controller
{
    public function __invoke(Request $request, string $token, SupportAccess $support): RedirectResponse
    {
        $user = $support->consume($token);

        if (! $user) {
            return redirect()->route('admin.session.create')->with('error', trans('platform::app.support.expired'));
        }

        auth()->guard('user')->login($user);
        $request->session()->regenerate();
        $request->session()->put('platform_support', true);

        // Support sessions do not stop at the two-factor challenge.
        $manager = 'Webkul\\Security\\Services\\TwoFactorManager';

        if (class_exists($manager)) {
            app($manager)->markPassed($request, $user);
        }

        $support->log('support_login', $user, 'platform support');

        return redirect()->route('admin.dashboard.index');
    }
}
