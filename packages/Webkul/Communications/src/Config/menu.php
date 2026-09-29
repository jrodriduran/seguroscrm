<?php

return [
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
