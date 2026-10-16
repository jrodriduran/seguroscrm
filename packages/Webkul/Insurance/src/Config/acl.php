<?php

return [
    [
        'key' => 'leads.family_transitions',
        'name' => 'admin::app.family-workflow.title',
        'route' => [
            'admin.leads.family_transitions.index', 'admin.leads.family_transitions.store',
            'admin.leads.family_transitions.resolve', 'admin.leads.family_transitions.individual',
        ],
        'sort' => 31,
    ],
    [
        'key' => 'leads.healthsherpa',
        'name' => 'admin::app.healthsherpa.permission',
        'route' => [
            'admin.insurance.healthsherpa.readiness',
            'admin.insurance.healthsherpa.counties',
            'admin.leads.healthsherpa.quotes',
        ],
        'sort' => 30,
    ],
];
