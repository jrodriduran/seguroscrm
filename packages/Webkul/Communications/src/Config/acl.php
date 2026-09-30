<?php

return [
    [
        'key' => 'mail.communication_sequences',
        'name' => 'communications::app.sequences.title',
        'route' => [
            'admin.communications.sequences.index', 'admin.communications.sequences.create', 'admin.communications.sequences.store',
            'admin.communications.sequences.edit', 'admin.communications.sequences.update', 'admin.communications.sequences.toggle',
            'admin.communications.sequences.delete',
        ],
        'sort' => 6,
    ], [
        'key' => 'mail.communication_templates',
        'name' => 'communications::app.templates.title',
        'route' => [
            'admin.communications.templates.index', 'admin.communications.templates.create', 'admin.communications.templates.store',
            'admin.communications.templates.settings', 'admin.communications.templates.edit', 'admin.communications.templates.update',
            'admin.communications.templates.delete',
        ],
        'sort' => 7,
    ], [
        'key' => 'contacts.persons.sequences',
        'name' => 'communications::app.acl.sequences',
        'route' => [
            'admin.communications.enrollments.store', 'admin.communications.enrollments.pause',
            'admin.communications.enrollments.resume', 'admin.communications.enrollments.stop',
        ],
        'sort' => 9,
    ], [
        'key' => 'settings.communications.stages',
        'name' => 'communications::app.stages.title',
        'route' => ['admin.settings.communications.stages.index', 'admin.settings.communications.stages.store', 'admin.settings.communications.stages.delete'],
        'sort' => 0,
    ],
    [
        'key' => 'contacts.persons.communications',
        'name' => 'communications::app.acl.communications',
        'route' => ['admin.communications.persons.show', 'admin.communications.zip'],
        'sort' => 7,
    ], [
        'key' => 'contacts.persons.consent',
        'name' => 'communications::app.acl.consent',
        'route' => ['admin.communications.persons.preferences', 'admin.communications.persons.consent', 'admin.communications.persons.reply'],
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
    ], [
        'key' => 'settings.communications.chatwoot',
        'name' => 'communications::app.chatwoot.title',
        'route' => [
            'admin.settings.communications.chatwoot.index',
            'admin.settings.communications.chatwoot.update',
            'admin.settings.communications.chatwoot.disconnect',
        ],
        'sort' => 2,
    ],
];
