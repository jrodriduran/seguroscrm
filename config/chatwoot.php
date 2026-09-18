<?php

return [
    /**
     * Chatwoot instance base URL (e.g. https://app.chatwoot.com or your self-hosted domain).
     */
    'base_url' => env('CHATWOOT_BASE_URL', 'https://app.chatwoot.com'),

    /**
     * Chatwoot Account ID.
     */
    'account_id' => env('CHATWOOT_ACCOUNT_ID', '1'),

    /**
     * Chatwoot API User or Agent Token.
     */
    'api_token' => env('CHATWOOT_API_TOKEN', ''),

    /**
     * Default Inbox ID for health insurance WhatsApp / SMS incoming channels.
     */
    'default_inbox_id' => env('CHATWOOT_DEFAULT_INBOX_ID'),

    /**
     * Optional verification token for incoming webhooks.
     */
    'webhook_verify_token' => env('CHATWOOT_WEBHOOK_VERIFY_TOKEN'),
];
