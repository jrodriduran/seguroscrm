<?php

return [
    'warning' => [
        'title' => 'Account notice',
        'default' => 'There is a pending payment. Please contact support to avoid interruptions.',
    ],

    'read-only' => [
        'title' => 'Account in read-only mode',
        'default' => 'You can view and export your information, but not create or change anything until the account is settled.',
        'blocked' => 'The account is in read-only mode: nothing was saved. Please contact support.',
    ],

    'suspended' => [
        'title' => 'Account suspended',
        'default' => 'Access is temporarily suspended. Please contact support to reactivate it.',
        'data-safe' => 'Your information is safe and nothing has been deleted.',
        'logout' => 'Sign out',
    ],

    'support' => [
        'banner' => 'Platform support session: it is recorded in the agency access log.',
        'expired' => 'The support link has expired or was already used.',
    ],
];
