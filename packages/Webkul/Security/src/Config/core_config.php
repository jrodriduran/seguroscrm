<?php

return [
    [
        'key' => 'general.security',
        'name' => 'security::app.configuration.title',
        'info' => 'security::app.configuration.info',
        'icon' => 'icon-role',
        'sort' => 5,
    ], [
        'key' => 'general.security.ip_restriction',
        'name' => 'security::app.configuration.ip-restriction.title',
        'info' => 'security::app.configuration.ip-restriction.info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'enabled',
                'title' => 'security::app.configuration.ip-restriction.enabled',
                'type' => 'boolean',
                'default' => '0',
            ], [
                'name' => 'allowed_ips',
                'title' => 'security::app.configuration.ip-restriction.allowed-ips',
                'info' => 'security::app.configuration.ip-restriction.allowed-ips-info',
                'type' => 'text',
            ], [
                'name' => 'exempt_admins',
                'title' => 'security::app.configuration.ip-restriction.exempt-admins',
                'info' => 'security::app.configuration.ip-restriction.exempt-admins-info',
                'type' => 'boolean',
                'default' => '1',
            ],
        ],
    ], [
        'key' => 'general.security.two_factor',
        'name' => 'security::app.configuration.two-factor.title',
        'info' => 'security::app.configuration.two-factor.info',
        'sort' => 2,
        'fields' => [
            [
                'name' => 'required_for',
                'title' => 'security::app.configuration.two-factor.required-for',
                'type' => 'select',
                'default' => 'none',
                'options' => [
                    [
                        'title' => 'security::app.configuration.two-factor.required-none',
                        'value' => 'none',
                    ], [
                        'title' => 'security::app.configuration.two-factor.required-admins',
                        'value' => 'admins',
                    ], [
                        'title' => 'security::app.configuration.two-factor.required-all',
                        'value' => 'all',
                    ],
                ],
            ], [
                'name' => 'trust_days',
                'title' => 'security::app.configuration.two-factor.trust-days',
                'info' => 'security::app.configuration.two-factor.trust-days-info',
                'type' => 'number',
                'default' => '30',
            ],
        ],
    ],
];
