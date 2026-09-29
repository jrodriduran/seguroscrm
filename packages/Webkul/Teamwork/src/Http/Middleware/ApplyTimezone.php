<?php

namespace Webkul\Teamwork\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Teamwork\Support\Timezones;

/**
 * Applies the agency time zone (Configuration > General > Time zone) to the
 * whole request — or each user's computer time zone, when enabled, which
 * the browser reports in the crm_tz cookie.
 */
class ApplyTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $zone = static::resolve($request->cookie('crm_tz'));

        config(['app.timezone' => $zone]);

        date_default_timezone_set($zone);

        return $next($request);
    }

    /**
     * The zone to use: the browser's (if allowed and valid), else the agency's.
     */
    public static function resolve(?string $browserZone = null): string
    {
        try {
            $agencyZone = core()->getConfigData('general.general.timezone.timezone');
            $followBrowser = (bool) core()->getConfigData('general.general.timezone.follow_browser');
        } catch (\Throwable) {
            return config('app.timezone');
        }

        if ($followBrowser && Timezones::isValid($browserZone)) {
            return $browserZone;
        }

        return Timezones::isValid($agencyZone) ? $agencyZone : config('app.timezone');
    }
}
