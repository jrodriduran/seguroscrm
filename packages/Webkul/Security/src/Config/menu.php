<?php

return [
    [
        'key' => 'settings.security',
        'name' => 'security::app.menu.security',
        'info' => 'security::app.menu.security-info',
        'route' => 'admin.settings.security.access_logs.index',
        'sort' => 7,
        'icon-class' => 'icon-role',
    ], [
        'key' => 'settings.security.access_logs',
        'name' => 'security::app.menu.access-logs',
        'info' => 'security::app.menu.access-logs-info',
        'route' => 'admin.settings.security.access_logs.index',
        'sort' => 1,
        'icon-class' => 'icon-activity',
    ], [
        'key' => 'settings.security.two_factor',
        'name' => 'security::app.menu.two-factor',
        'info' => 'security::app.menu.two-factor-info',
        'route' => 'admin.settings.security.two_factor.index',
        'sort' => 2,
        'icon-class' => 'icon-role',
    ],
];
