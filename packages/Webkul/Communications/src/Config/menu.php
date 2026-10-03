<?php

return [
    [
        'key' => 'mail.communication_campaigns',
        'name' => 'communications::app.campaigns.title',
        'route' => 'admin.communications.campaigns.index',
        'sort' => 5,
        'icon-class' => '',
    ], [
        'key' => 'mail.communication_audiences',
        'name' => 'communications::app.audiences.title',
        'route' => 'admin.communications.audiences.index',
        'sort' => 8,
        'icon-class' => '',
    ],
    [
        'key' => 'mail.communication_sequences',
        'name' => 'communications::app.sequences.title',
        'route' => 'admin.communications.sequences.index',
        'sort' => 6,
        'icon-class' => '',
    ], [
        'key' => 'mail.communication_templates',
        'name' => 'communications::app.templates.title',
        'route' => 'admin.communications.templates.index',
        'sort' => 7,
        'icon-class' => '',
    ], [
        'key' => 'settings.communications.stages',
        'name' => 'communications::app.stages.title',
        'info' => 'communications::app.menu.stages-info',
        'route' => 'admin.settings.communications.stages.index',
        'sort' => 0,
        'icon-class' => 'icon-settings-flow',
    ],
    [
        'key' => 'settings.communications',
        'name' => 'communications::app.menu.communications',
        'info' => 'communications::app.menu.communications-info',
        'route' => 'admin.settings.communications.outcomes.index',
        'sort' => 7,
        'icon-class' => 'icon-mail',
    ], [
        'key' => 'settings.communications.outcomes',
        'name' => 'communications::app.outcomes.title',
        'info' => 'communications::app.menu.outcomes-info',
        'route' => 'admin.settings.communications.outcomes.index',
        'sort' => 1,
        'icon-class' => 'icon-activity',
    ], [
        'key' => 'settings.communications.chatwoot',
        'name' => 'communications::app.chatwoot.title',
        'info' => 'communications::app.menu.chatwoot-info',
        'route' => 'admin.settings.communications.chatwoot.index',
        'sort' => 2,
        'icon-class' => 'icon-mail',
    ],
];
