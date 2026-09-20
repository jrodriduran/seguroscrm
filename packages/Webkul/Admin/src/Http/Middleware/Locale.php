<?php

namespace Webkul\Admin\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

class Locale
{
    /**
     * The middleware instance.
     *
     * @return void
     */
    public function __construct(
        Application $app,
        Request $request
    ) {
        $this->app = $app;
        $this->request = $request;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $supported = ['en', 'es', 'pt_BR', 'tr', 'ar', 'fa', 'ja', 'ko', 'vi', 'zh_CN'];
        $locale = null;

        if ($requested = $request->get('locale')) {
            if (in_array($requested, $supported)) {
                $locale = $requested;
            } elseif ($requested === 'pt') {
                $locale = 'pt_BR';
            }
            if ($locale) {
                session(['locale' => $locale]);
                cookie()->queue('krayin_locale', $locale, 60 * 24 * 365);
            }
        }

        if (! $locale) {
            $locale = session('locale')
                ?: $request->cookie('krayin_locale')
                ?: core()->getConfigData('general.general.locale_settings.locale')
                ?: config('app.locale', 'en');
        }

        if (! in_array($locale, $supported)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
