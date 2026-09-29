<?php

return [
    /**
     * Insurance modules that shipped without ACL entries. The routes are
     * mapped to these keys by EnforceRoutePermissions for custom roles.
     */
    [
        'key' => 'financials',
        'name' => 'security::app.acl.financials',
        'route' => 'admin.security.financials.none',
        'sort' => 2,
    ], [
        'key' => 'policies',
        'name' => 'security::app.acl.policies',
        'route' => 'admin.policies.index',
        'sort' => 3,
    ], [
        'key' => 'policies.edit',
        'name' => 'security::app.acl.edit',
        'route' => 'admin.policies.renew',
        'sort' => 1,
    ], [
        'key' => 'commissions',
        'name' => 'security::app.acl.commissions',
        'route' => 'admin.commissions.index',
        'sort' => 4,
    ], [
        'key' => 'commissions.edit',
        'name' => 'security::app.acl.edit',
        'route' => 'admin.commissions.update',
        'sort' => 1,
    ], [
        'key' => 'hierarchy',
        'name' => 'security::app.acl.hierarchy',
        'route' => 'admin.hierarchy.index',
        'sort' => 5,
    ], [
        'key' => 'hierarchy.edit',
        'name' => 'security::app.acl.edit',
        'route' => 'admin.hierarchy.save',
        'sort' => 1,
    ], [
        'key' => 'insurance_analytics',
        'name' => 'security::app.acl.analytics',
        'route' => 'admin.insurance.analytics.index',
        'sort' => 6,
    ], [
        'key' => 'configuration.edit',
        'name' => 'security::app.acl.edit',
        'route' => 'admin.configuration.store',
        'sort' => 1,
    ], [
        'key' => 'settings.security',
        'name' => 'security::app.menu.security',
        'route' => 'admin.settings.security.access_logs.index',
        'sort' => 7,
    ], [
        'key' => 'settings.security.access_logs',
        'name' => 'security::app.menu.access-logs',
        'route' => 'admin.settings.security.access_logs.index',
        'sort' => 1,
    ], [
        'key' => 'settings.security.two_factor',
        'name' => 'security::app.menu.two-factor',
        'route' => 'admin.settings.security.two_factor.index',
        'sort' => 2,
    ], [
        'key' => 'settings.security.two_factor.reset',
        'name' => 'security::app.two-factor.users.reset',
        'route' => 'admin.settings.security.two_factor.reset',
        'sort' => 1,
    ],
];
