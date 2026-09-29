<?php

namespace Webkul\Teamwork\Providers;

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Teamwork\Console\RunAutomations;
use Webkul\Teamwork\Http\Middleware\ApplyTimezone;
use Webkul\Teamwork\Listeners\RecordActivityListener;
use Webkul\Teamwork\Services\BusinessHours;
use Webkul\Teamwork\Services\Mentions;
use Webkul\Teamwork\Services\TeamScope;

class TeamworkServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'teamwork');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'teamwork');

        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path'))
            ->group(__DIR__.'/../Routes/admin.php');

        // Agency time zone for every request (and for scheduled commands).
        // First in the stack, so everything after it (sessions, 2FA, logs) uses the right zone.
        $this->app['router']->prependMiddlewareToGroup('web', ApplyTimezone::class);

        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $zone = ApplyTimezone::resolve();

                config(['app.timezone' => $zone]);
                date_default_timezone_set($zone);
            });
        }

        // Followers hear about work done on the records they follow.
        Event::listen('activity.create.after', [RecordActivityListener::class, 'activityCreated']);
        Event::listen('lead.update.before', [RecordActivityListener::class, 'leadUpdating']);
        Event::listen('lead.update.after', [RecordActivityListener::class, 'leadUpdated']);
        Event::listen('lead.create.after', [RecordActivityListener::class, 'leadCreated']);

        // Date-based automations (renewals, overdue cases) run every hour.
        if ($this->app->runningInConsole()) {
            $this->commands([RunAutomations::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('teamwork:run-automations')->hourly()->withoutOverlapping();
        });

        $this->app->booted(function () {
            Breadcrumbs::for('teamwork.center', function (BreadcrumbTrail $trail) {
                $trail->parent('dashboard');
                $trail->push(trans('teamwork::app.center.title'), route('admin.teamwork.center'));
            });

            Breadcrumbs::for('teamwork.follow_up', function (BreadcrumbTrail $trail, $followUp) {
                $trail->parent('teamwork.center');
                $trail->push('#'.$followUp->id, route('admin.teamwork.follow_ups.show', $followUp->id));
            });

            Breadcrumbs::for('teamwork.notifications', function (BreadcrumbTrail $trail) {
                $trail->parent('dashboard');
                $trail->push(trans('teamwork::app.notifications.title'), route('admin.teamwork.notifications.index'));
            });

            Breadcrumbs::for('settings.teamwork.automations', function (BreadcrumbTrail $trail) {
                $trail->parent('settings');
                $trail->push(trans('teamwork::app.automations.title'), route('admin.settings.teamwork.automations.index'));
            });

            Breadcrumbs::for('settings.teamwork.rules', function (BreadcrumbTrail $trail) {
                $trail->parent('settings');
                $trail->push(trans('teamwork::app.rules.title'), route('admin.settings.teamwork.rules.index'));
            });
        });
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        // One per request: they cache configuration and team lookups.
        $this->app->scoped(BusinessHours::class);
        $this->app->scoped(TeamScope::class);
        $this->app->scoped(Mentions::class);

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/core_config.php', 'core_config');
    }
}
