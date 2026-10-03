<?php

return [
    [
        'key' => 'follow_up',
        'name' => 'teamwork::app.center.title',
        'route' => 'admin.teamwork.center',
        'sort' => 1,
    ], [
        'key' => 'settings.teamwork',
        'name' => 'teamwork::app.menu.teamwork',
        'route' => 'admin.settings.teamwork.rules.index',
        'sort' => 6,
    ], [
        'key' => 'settings.teamwork.automations',
        'name' => 'teamwork::app.automations.title',
        'route' => ['admin.settings.teamwork.automations.index', 'admin.settings.teamwork.automations.store', 'admin.settings.teamwork.automations.toggle', 'admin.settings.teamwork.automations.delete'],
        'sort' => 2,
    ], [
        'key' => 'settings.teamwork.playbook',
        'name' => 'teamwork::app.playbook.title',
        'route' => ['admin.settings.teamwork.playbook.index', 'admin.settings.teamwork.playbook.update'],
        'sort' => 0,
    ], [
        'key' => 'settings.teamwork.rules',
        'name' => 'teamwork::app.rules.title',
        'route' => ['admin.settings.teamwork.rules.index', 'admin.settings.teamwork.rules.store', 'admin.settings.teamwork.rules.toggle', 'admin.settings.teamwork.rules.delete'],
        'sort' => 1,
    ],
];
