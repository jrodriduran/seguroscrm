<?php

namespace Webkul\Security\Providers;

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Security\Console\DisableIpRestriction;
use Webkul\Security\Http\Middleware\EnforceRoutePermissions;
use Webkul\Security\Http\Middleware\EnforceTwoFactor;
use Webkul\Security\Http\Middleware\RestrictIpAccess;
use Webkul\Security\Listeners\RecordAccessEvent;

class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'security');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'security');

        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path'))
            ->group(__DIR__.'/../Routes/admin.php');

        // Run inside the "web" stack after routing, so they can tell the sign-in form and challenge routes apart.
        $router->pushMiddlewareToGroup('web', RestrictIpAccess::class);
        $router->pushMiddlewareToGroup('web', EnforceTwoFactor::class);
        $router->pushMiddlewareToGroup('web', EnforceRoutePermissions::class);

        Event::listen(Login::class, [RecordAccessEvent::class, 'handleLogin']);
        Event::listen(Logout::class, [RecordAccessEvent::class, 'handleLogout']);
        Event::listen(Failed::class, [RecordAccessEvent::class, 'handleFailed']);

        $this->registerBreadcrumbs();

        if ($this->app->runningInConsole()) {
            $this->commands([DisableIpRestriction::class]);
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/core_config.php', 'core_config');
    }

    /**
     * Dashboard > Settings > Security > Access Log.
     */
    protected function registerBreadcrumbs(): void
    {
        $this->app->booted(function () {
            Breadcrumbs::for('settings.security.access_logs', function (BreadcrumbTrail $trail) {
                $trail->parent('settings');
                $trail->push(trans('security::app.access-logs.title'), route('admin.settings.security.access_logs.index'));
            });

            Breadcrumbs::for('settings.security.two_factor', function (BreadcrumbTrail $trail) {
                $trail->parent('settings');
                $trail->push(trans('security::app.menu.two-factor'), route('admin.settings.security.two_factor.index'));
            });

            Breadcrumbs::for('account.security', function (BreadcrumbTrail $trail) {
                $trail->parent('dashboard');
                $trail->push(trans('security::app.two-factor.setup.title'), route('admin.security.two_factor.setup'));
            });
        });
    }
}
