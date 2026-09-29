<?php

namespace Webkul\Communications\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Account-level Chatwoot calls: checking the connection, listing inboxes
 * (each is a channel: WhatsApp, SMS, Telegram, web chat…) and registering
 * the CRM webhook.
 */
class ChatwootAccount
{
    /**
     * Chatwoot inbox types mapped to the channel names the CRM uses.
     */
    public const CHANNELS = [
        'Channel::Whatsapp' => 'whatsapp',
        'Channel::TwilioSms' => 'sms',
        'Channel::Sms' => 'sms',
        'Channel::Telegram' => 'telegram',
        'Channel::WebWidget' => 'web',
        'Channel::Email' => 'email',
        'Channel::FacebookPage' => 'messenger',
        'Channel::Instagram' => 'instagram',
        'Channel::Line' => 'line',
        'Channel::Api' => 'api',
    ];

    public const WEBHOOK_EVENTS = ['message_created', 'message_updated', 'conversation_status_changed'];

    public function isConfigured(): bool
    {
        return (bool) (config('chatwoot.base_url') && config('chatwoot.account_id') && config('chatwoot.api_token'));
    }

    public static function channelFor(?string $channelType, ?string $medium = null): string
    {
        if ($channelType === 'Channel::TwilioSms' && $medium === 'whatsapp') {
            return 'whatsapp';
        }

        return self::CHANNELS[$channelType] ?? 'chat';
    }

    /**
     * Inboxes of the account, keyed by id, cached for a few minutes.
     *
     * @return array<int, array{id: int, name: string, channel_type: string, channel: string}>
     */
    public function inboxes(bool $fresh = false): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $key = 'chatwoot-inboxes:'.md5(config('chatwoot.base_url').config('chatwoot.account_id'));

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes(10), function () {
            $response = $this->client()->get($this->url('inboxes'));

            if (! $response->successful()) {
                throw new RuntimeException($this->error($response->status()));
            }

            return collect($response->json('payload', []))->mapWithKeys(fn ($inbox) => [(int) $inbox['id'] => [
                'id' => (int) $inbox['id'],
                'name' => (string) $inbox['name'],
                'channel_type' => (string) ($inbox['channel_type'] ?? ''),
                'channel' => self::channelFor($inbox['channel_type'] ?? null, $inbox['medium'] ?? null),
            ]])->all();
        });
    }

    public function channelOfInbox(?int $inboxId): ?string
    {
        if (! $inboxId) {
            return null;
        }

        try {
            return $this->inboxes()[$inboxId]['channel'] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Create the CRM webhook in Chatwoot, or update it if one points here.
     */
    public function registerWebhook(string $url): void
    {
        $existing = collect($this->client()->get($this->url('webhooks'))->json('payload.webhooks', []))
            ->first(fn ($webhook) => str_starts_with((string) ($webhook['url'] ?? ''), strtok($url, '?')));

        $payload = ['url' => $url, 'subscriptions' => self::WEBHOOK_EVENTS];

        $response = $existing
            ? $this->client()->patch($this->url('webhooks/'.$existing['id']), $payload)
            : $this->client()->post($this->url('webhooks'), $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->error($response->status(), (string) $response->json('message')));
        }
    }

    protected function client(): PendingRequest
    {
        return Http::timeout(8)->acceptJson()->withHeaders(['api_access_token' => (string) config('chatwoot.api_token')]);
    }

    protected function url(string $path): string
    {
        return rtrim((string) config('chatwoot.base_url'), '/').'/api/v1/accounts/'.config('chatwoot.account_id').'/'.$path;
    }

    protected function error(int $status, string $detail = ''): string
    {
        return match ($status) {
            401 => trans('communications::app.chatwoot.errors.token'),
            404 => trans('communications::app.chatwoot.errors.account'),
            default => trans('communications::app.chatwoot.errors.http', ['status' => $status]).($detail ? " — {$detail}" : ''),
        };
    }
}
