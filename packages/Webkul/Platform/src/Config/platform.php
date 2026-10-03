<?php

return [
    /**
     * Shared secret between this instance and the SaaS operator panel.
     * Every API call is signed with it (HMAC-SHA256). Empty = API disabled;
     * the artisan commands keep working.
     */
    'secret' => env('PLATFORM_SECRET'),

    /**
     * Seconds a signed API request stays valid (clock skew allowance).
     */
    'signature_ttl' => 300,

    /**
     * Seconds a support login link stays valid.
     */
    'support_link_ttl' => 60,
];
