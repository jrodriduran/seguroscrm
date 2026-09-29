<?php

return [
    [
        'key' => 'follow_up',
        'name' => 'teamwork::app.center.title',
        'route' => 'admin.teamwork.center',
        'sort' => 1,
        'icon-class' => 'icon-activity',
    ], [
        'key' => 'settings.teamwork',
        'name' => 'teamwork::app.menu.teamwork',
        'info' => 'teamwork::app.menu.teamwork-info',
        'route' => 'admin.settings.teamwork.rules.index',
        'sort' => 6,
        'icon-class' => 'icon-activity',
    ], [
        'key' => 'settings.teamwork.automations',
        'name' => 'teamwork::app.automations.title',
        'info' => 'teamwork::app.menu.automations-info',
        'route' => 'admin.settings.teamwork.automations.index',
        'sort' => 2,
        'icon-class' => 'icon-settings-flow',
    ], [
        'key' => 'settings.teamwork.rules',
        'name' => 'teamwork::app.rules.title',
        'info' => 'teamwork::app.menu.rules-info',
        'route' => 'admin.settings.teamwork.rules.index',
        'sort' => 1,
        'icon-class' => 'icon-activity',
    ],
];
