<?php

namespace Webkul\Admin\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'contacts.person.create.after' => [
            'Webkul\Admin\Listeners\Person@linkToEmail',
        ],

        'lead.create.after' => [
            'Webkul\Admin\Listeners\Lead@linkToEmail',
            'Webkul\Admin\Listeners\InsuranceSla@onLeadCreate',
        ],

        'lead.update.after' => [
            'Webkul\Admin\Listeners\InsuranceSla@onLeadUpdate',
        ],

        'activity.create.after' => [
            'Webkul\Admin\Listeners\Activity@afterUpdateOrCreate',
            'Webkul\Admin\Listeners\InsuranceSla@onActivitySaved',
        ],

        'activity.update.after' => [
            'Webkul\Admin\Listeners\Activity@afterUpdateOrCreate',
            'Webkul\Admin\Listeners\InsuranceSla@onActivitySaved',
        ],
    ];
}
