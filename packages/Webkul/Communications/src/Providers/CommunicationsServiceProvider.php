<?php

namespace Webkul\Communications\Providers;

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Communications\Console\BackfillAddresses;
use Webkul\Communications\Console\RunSequences;
use Webkul\Communications\Listeners\ActivityOutcomeListener;
use Webkul\Communications\Listeners\PolicyLifecycleObserver;
use Webkul\Communications\Listeners\SequenceTriggers;
use Webkul\Communications\Services\CommunicationSettings;
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

        $this->app->singleton(CommunicationSettings::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Chatwoot connection saved in Settings wins over the .env defaults.
        $this->app->make(CommunicationSettings::class)->applyToConfig();

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'communications');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'communications');

        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path'))
            ->group(__DIR__.'/../Routes/admin.php');

        Route::group([], __DIR__.'/../Routes/webhooks.php');

        Event::listen('activity.create.after', [ActivityOutcomeListener::class, 'saved']);
        Event::listen('activity.update.after', [ActivityOutcomeListener::class, 'saved']);

        // Communication sequences.
        Event::listen('lead.create.after', [SequenceTriggers::class, 'leadCreated']);
        Event::listen('lead.update.before', [SequenceTriggers::class, 'leadUpdating']);
        Event::listen('lead.update.after', [SequenceTriggers::class, 'leadUpdated']);
        Event::listen('communications.policy.effectuated', [SequenceTriggers::class, 'policyEffectuated']);
        Event::listen('communications.policy.status_changed', [SequenceTriggers::class, 'policyStatusChanged']);
        Event::listen('communications.call.outcome', [SequenceTriggers::class, 'callOutcome']);
        Event::listen('communications.message.received', [SequenceTriggers::class, 'messageReceived']);

        if (class_exists(InsurancePolicy::class)) {
            InsurancePolicy::observe(PolicyLifecycleObserver::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([BackfillAddresses::class, RunSequences::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('communications:run-sequences')->everyFiveMinutes()->withoutOverlapping();
        });

        $this->app->booted(function () {
            Breadcrumbs::for('communications.person', function (BreadcrumbTrail $trail, $person) {
                $trail->parent('contacts.persons.view', $person);
                $trail->push(trans('communications::app.person.title'), route('admin.communications.persons.show', $person->id));
            });

            Breadcrumbs::for('settings.communications.outcomes', function (BreadcrumbTrail $trail) {
                $trail->parent('settings');
                $trail->push(trans('communications::app.outcomes.title'), route('admin.settings.communications.outcomes.index'));
            });

            Breadcrumbs::for('settings.communications.stages', function (BreadcrumbTrail $trail) {
                $trail->parent('settings');
                $trail->push(trans('communications::app.stages.title'), route('admin.settings.communications.stages.index'));
            });

            Breadcrumbs::for('communications.templates', function (BreadcrumbTrail $trail) {
                $trail->push(trans('communications::app.templates.title'), route('admin.communications.templates.index'));
            });

            Breadcrumbs::for('communications.sequences', function (BreadcrumbTrail $trail) {
                $trail->push(trans('communications::app.sequences.title'), route('admin.communications.sequences.index'));
            });

            Breadcrumbs::for('settings.communications.chatwoot', function (BreadcrumbTrail $trail) {
                $trail->parent('settings');
                $trail->push(trans('communications::app.chatwoot.title'), route('admin.settings.communications.chatwoot.index'));
            });
        });
    }
}
