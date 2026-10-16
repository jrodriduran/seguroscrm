<?php

namespace Webkul\Insurance\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkul\Lead\Repositories\LeadRepository;

class AuthorizeInsuranceLead
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(auth()->guard('user')->check(), 401);
        $lead = app(LeadRepository::class)->findOrFail($request->route('lead_id'));
        $owners = bouncer()->getAuthorizedUserIds();
        abort_unless($owners === null || in_array($lead->user_id, $owners), 401);

        return $next($request);
    }
}
