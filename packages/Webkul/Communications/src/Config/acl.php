<?php

return [
    [
        'key' => 'contacts.persons.communications',
        'name' => 'communications::app.acl.communications',
        'route' => ['admin.communications.persons.show', 'admin.communications.zip'],
        'sort' => 7,
    ], [
        'key' => 'contacts.persons.consent',
        'name' => 'communications::app.acl.consent',
        'route' => ['admin.communications.persons.preferences', 'admin.communications.persons.consent'],
        'sort' => 8,
    ], [
        'key' => 'settings.communications',
        'name' => 'communications::app.menu.communications',
        'route' => 'admin.settings.communications.outcomes.index',
        'sort' => 7,
    ], [
        'key' => 'settings.communications.outcomes',
        'name' => 'communications::app.outcomes.title',
        'route' => [
            'admin.settings.communications.outcomes.index',
            'admin.settings.communications.outcomes.store',
            'admin.settings.communications.outcomes.update',
            'admin.settings.communications.outcomes.toggle',
            'admin.settings.communications.outcomes.move',
            'admin.settings.communications.outcomes.delete',
        ],
        'sort' => 1,
    ],
];
