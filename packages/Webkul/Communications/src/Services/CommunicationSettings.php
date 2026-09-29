<?php

namespace Webkul\Communications\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Channel settings saved from the CRM (no server access needed). Secret
 * values are encrypted with the app key and never sent back to the browser.
 */
class CommunicationSettings
{
    public const SECRETS = ['chatwoot.api_token', 'chatwoot.webhook_token'];

    protected ?array $values = null;

    public function get(string $key, $default = null)
    {
        $value = $this->all()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public function set(string $key, $value): void
    {
        $stored = $value === null || $value === '' ? null : (string) $value;

        if ($stored !== null && in_array($key, self::SECRETS, true)) {
            $stored = Crypt::encryptString($stored);
        }

        DB::table('communication_settings')->updateOrInsert(['key' => $key], ['value' => $stored, 'updated_at' => now()]);

        $this->values = null;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Secret the CRM puts in the webhook URL so only Chatwoot can post to it.
     */
    public function webhookToken(): string
    {
        if (! $token = $this->get('chatwoot.webhook_token')) {
            $this->set('chatwoot.webhook_token', $token = Str::random(40));
        }

        return $token;
    }

    /**
     * Saved values win over the .env defaults in config/chatwoot.php.
     */
    public function applyToConfig(): void
    {
        foreach (['base_url', 'account_id', 'api_token', 'default_inbox_id'] as $key) {
            if (($value = $this->get("chatwoot.{$key}")) !== null) {
                config(["chatwoot.{$key}" => $value]);
            }
        }
    }

    protected function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        try {
            if (! Schema::hasTable('communication_settings')) {
                return $this->values = [];
            }

            $rows = DB::table('communication_settings')->pluck('value', 'key')->all();
        } catch (Throwable) {
            return $this->values = [];
        }

        foreach (self::SECRETS as $key) {
            if (! empty($rows[$key])) {
                try {
                    $rows[$key] = Crypt::decryptString($rows[$key]);
                } catch (DecryptException) {
                    $rows[$key] = null;
                }
            }
        }

        return $this->values = $rows;
    }
}
