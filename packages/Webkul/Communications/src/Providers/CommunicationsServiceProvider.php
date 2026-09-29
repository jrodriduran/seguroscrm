<?php

namespace Webkul\Communications\Providers;

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Client communications across channels. Today: each client's timeline.
 * Next: the shared inbox for every inbound channel through Chatwoot.
 */
class CommunicationsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'communications');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'communications');

        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path'))
            ->group(__DIR__.'/../Routes/admin.php');

        $this->app->booted(function () {
            Breadcrumbs::for('communications.person', function (BreadcrumbTrail $trail, $person) {
                $trail->parent('contacts.persons.view', $person);
                $trail->push(trans('communications::app.person.title'), route('admin.communications.persons.show', $person->id));
            });
        });
    }
}
