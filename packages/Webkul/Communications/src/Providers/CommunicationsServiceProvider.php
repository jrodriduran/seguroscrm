<?php

namespace Webkul\Communications\Providers;

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Communications\Console\BackfillAddresses;
use Webkul\Communications\Listeners\ActivityOutcomeListener;
use Webkul\Communications\Listeners\PolicyLifecycleObserver;
use Webkul\Lead\Models\InsurancePolicy;

/**
 * Client communications across channels: each client's timeline, how they
 * want to be reached and what they agreed to, and call outcomes.
 * Next: communication sequences and the shared inbox (Chatwoot).
 */
class CommunicationsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'communications');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'communications');

        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path'))
            ->group(__DIR__.'/../Routes/admin.php');

        Event::listen('activity.create.after', [ActivityOutcomeListener::class, 'saved']);
        Event::listen('activity.update.after', [ActivityOutcomeListener::class, 'saved']);

        if (class_exists(InsurancePolicy::class)) {
            InsurancePolicy::observe(PolicyLifecycleObserver::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([BackfillAddresses::class]);
        }

        $this->app->booted(function () {
            Breadcrumbs::for('communications.person', function (BreadcrumbTrail $trail, $person) {
                $trail->parent('contacts.persons.view', $person);
                $trail->push(trans('communications::app.person.title'), route('admin.communications.persons.show', $person->id));
            });

            Breadcrumbs::for('settings.communications.outcomes', function (BreadcrumbTrail $trail) {
                $trail->parent('settings');
                $trail->push(trans('communications::app.outcomes.title'), route('admin.settings.communications.outcomes.index'));
            });
        });
    }
}
