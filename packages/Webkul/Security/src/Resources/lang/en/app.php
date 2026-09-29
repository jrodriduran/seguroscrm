<?php

return [
    'acl' => [
        'financials' => 'Financial information (revenue, commissions)',
        'policies' => 'Policies',
        'commissions' => 'Commissions',
        'hierarchy' => 'Hierarchy & Overrides',
        'analytics' => 'Analytics & Valuation',
        'edit' => 'Edit',
    ],

    'menu' => [
        'security' => 'Security',
        'security-info' => 'Access log, two-factor authentication and sign-in protection.',
        'access-logs' => 'Access Log',
        'access-logs-info' => 'Who signed in, when, and from which IP address.',
        'two-factor' => 'Two-Factor Authentication',
        'two-factor-info' => 'See who has two-factor on and reset it for a lost phone.',
    ],

    'access-logs' => [
        'title' => 'Access Log',

        'datagrid' => [
            'date' => 'Date',
            'user' => 'User',
            'email' => 'Email',
            'event' => 'Event',
            'ip' => 'IP Address',
            'device' => 'Device',
        ],

        'events' => [
            'login' => 'Signed in',
            'logout' => 'Signed out',
            'failed' => 'Failed attempt',
            'blocked_ip' => 'Blocked IP',
            'mfa_failed' => 'Wrong 2FA code',
            'mfa_enabled' => '2FA enabled',
            'mfa_disabled' => '2FA disabled',
            'mfa_reset' => '2FA reset by admin',
        ],
    ],

    'ip-restriction' => [
        'denied' => 'Access from your network is not allowed. Contact your administrator.',
    ],

    'two-factor' => [
        'challenge' => [
            'title' => 'Two-step verification',
            'info' => 'Enter the 6-digit code from your authenticator app.',
            'code' => 'Verification code',
            'recovery-hint' => 'Lost your phone? Enter one of your recovery codes instead.',
            'trust' => 'Trust this browser for :days days',
            'sign-out' => 'Sign out',
            'verify' => 'Verify',
            'invalid' => 'That code is not valid. Check your app and try again.',
            'throttled' => 'Too many attempts. Try again in :seconds seconds.',
            'required' => 'Two-step verification required.',
        ],

        'setup' => [
            'title' => 'Account Security',
            'heading' => 'Two-step verification',
            'info' => 'Protect your account with a code from your phone in addition to your password.',
            'status-on' => 'On',
            'status-off' => 'Off',
            'required-notice' => 'Your organization requires two-step verification. Set it up to continue using the CRM.',
            'recovery-title' => 'Save your recovery codes',
            'recovery-info' => 'Each code works once if you lose access to your phone. Store them somewhere safe: they will not be shown again.',
            'copy' => 'Copy codes',
            'step-app' => 'Install an authenticator app on your phone: Google Authenticator, Microsoft Authenticator or Authy.',
            'step-scan' => 'Scan this QR code with the app, or type the key manually.',
            'manual-key' => 'Setup key',
            'step-confirm' => 'Enter the 6-digit code the app shows to finish.',
            'enable' => 'Enable two-step verification',
            'enabled-success' => 'Two-step verification is on.',
            'recovery-codes' => 'Recovery codes',
            'remaining' => ':count unused recovery codes left.',
            'current-code' => 'Current code',
            'regenerate' => 'Generate new codes',
            'disable-title' => 'Turn off',
            'disable-info' => 'Your account will be protected by your password only.',
            'disable' => 'Turn off two-step verification',
            'disabled-success' => 'Two-step verification is off.',
            'cannot-disable' => 'Your organization requires two-step verification, so it cannot be turned off.',
            'devices' => 'Trusted browsers',
            'forget-devices' => 'Forget all',
            'devices-forgotten' => 'Trusted browsers removed. They will ask for a code next time.',
            'last-used' => 'last used :date',
            'no-devices' => 'No trusted browsers. Tick "Trust this browser" when you enter a code to skip it for a while.',
        ],

        'users' => [
            'datagrid' => [
                'name' => 'Name',
                'email' => 'Email',
                'role' => 'Role',
                'status' => 'Two-factor',
                'devices' => 'Trusted browsers',
            ],

            'enabled-since' => 'On since :date',
            'disabled' => 'Off',
            'reset' => 'Reset two-factor',
            'reset-success' => 'Two-factor was reset for :name.',
            'my-account' => 'My two-factor',
        ],
    ],

    'configuration' => [
        'title' => 'Security',
        'info' => 'Sign-in protection for your team: IP allowlist and two-step verification.',

        'ip-restriction' => [
            'title' => 'IP Address Restriction',
            'info' => 'Only allow the admin panel from specific IP addresses, such as your office.',
            'enabled' => 'Enable IP restriction',
            'allowed-ips' => 'Allowed IP addresses',
            'allowed-ips-info' => 'Separate with commas. Single IPs (203.0.113.10) or ranges (192.168.1.0/24) are accepted.',
            'exempt-admins' => 'Administrators can sign in from anywhere',
            'exempt-admins-info' => 'Recommended: prevents locking everyone out if the list is wrong.',
        ],

        'two-factor' => [
            'title' => 'Two-Step Verification',
            'info' => 'Ask for a code from an authenticator app after the password.',
            'required-for' => 'Require two-step verification for',
            'required-none' => 'Nobody (optional for each user)',
            'required-admins' => 'Administrators',
            'required-all' => 'Everyone',
            'trust-days' => 'Days a trusted browser skips the code',
            'trust-days-info' => '0 disables trusted browsers.',
        ],
    ],
];
