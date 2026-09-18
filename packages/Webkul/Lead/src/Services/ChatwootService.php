<?php

namespace Webkul\Lead\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;

class ChatwootService
{
    protected string $baseUrl;
    protected string $accountId;
    protected string $apiToken;
    protected ?int $defaultInboxId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('chatwoot.base_url', 'https://app.chatwoot.com'), '/');
        $this->accountId = (string) config('chatwoot.account_id', '1');
        $this->apiToken = (string) config('chatwoot.api_token', '');
        $this->defaultInboxId = config('chatwoot.default_inbox_id') ? (int) config('chatwoot.default_inbox_id') : null;
    }

    /**
     * Check if Chatwoot API integration is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiToken) && ! empty($this->accountId);
    }

    /**
     * Find existing Chatwoot contact or create a new one.
     */
    public function findOrCreateContact(Person $person, ?Lead $lead = null): ?int
    {
        if ($person->chatwoot_contact_id) {
            return (int) $person->chatwoot_contact_id;
        }

        $email = $person->emails?->first()?->value ?: ($person->emails[0]['value'] ?? null);
        $phone = $person->contact_numbers?->first()?->value ?: ($person->contact_numbers[0]['value'] ?? null);

        // Normalize phone number (E.164 without dashes/spaces, add +1 if US 10 digits)
        $cleanPhone = $this->formatPhoneE164($phone);

        // 1. Search existing contact by email or phone
        $existingId = $this->searchContact($cleanPhone ?: $email);
        if ($existingId) {
            $person->update(['chatwoot_contact_id' => $existingId]);
            return $existingId;
        }

        // 2. Create new contact
        try {
            $url = "{$this->baseUrl}/api/v1/accounts/{$this->accountId}/contacts";

            $payload = [
                'name' => $person->name,
                'email' => $email,
                'phone_number' => $cleanPhone,
                'custom_attributes' => [
                    'crm_person_id' => $person->id,
                    'crm_lead_id' => $lead?->id,
                ],
            ];

            $response = Http::withHeaders($this->getHeaders())->post($url, array_filter($payload));

            if ($response->successful()) {
                $contactId = $response->json('payload.contact.id') ?? $response->json('id');
                if ($contactId) {
                    $person->update(['chatwoot_contact_id' => $contactId]);
                    return (int) $contactId;
                }
            }
        } catch (\Throwable $e) {
            Log::error('Chatwoot createContact failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Create a new conversation with an initial outbound or inbound message.
     */
    public function createConversation(int $contactId, string $initialMessage, ?int $inboxId = null): ?array
    {
        $targetInboxId = $inboxId ?: $this->defaultInboxId;

        try {
            $url = "{$this->baseUrl}/api/v1/accounts/{$this->accountId}/conversations";

            $payload = [
                'contact_id' => $contactId,
                'inbox_id' => $targetInboxId,
                'message' => [
                    'content' => $initialMessage,
                ],
            ];

            $response = Http::withHeaders($this->getHeaders())->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::error('Chatwoot createConversation failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Send an outbound message in an active conversation.
     */
    public function sendMessage(int $conversationId, string $content): ?array
    {
        try {
            $url = "{$this->baseUrl}/api/v1/accounts/{$this->accountId}/conversations/{$conversationId}/messages";

            $payload = [
                'content' => $content,
                'message_type' => 'outgoing',
                'private' => false,
            ];

            $response = Http::withHeaders($this->getHeaders())->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::error('Chatwoot sendMessage failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Retrieve recent messages for a conversation.
     */
    public function getMessages(int $conversationId): array
    {
        try {
            $url = "{$this->baseUrl}/api/v1/accounts/{$this->accountId}/conversations/{$conversationId}/messages";

            $response = Http::withHeaders($this->getHeaders())->get($url);

            if ($response->successful()) {
                $payload = $response->json('payload') ?? $response->json();
                return is_array($payload) ? $payload : [];
            }
        } catch (\Throwable $e) {
            Log::error('Chatwoot getMessages failed: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Get direct Chatwoot agent web URL for a conversation.
     */
    public function getConversationUrl(int $conversationId): string
    {
        return "{$this->baseUrl}/app/accounts/{$this->accountId}/conversations/{$conversationId}";
    }

    /**
     * Search contact by query string (phone or email).
     */
    protected function searchContact(?string $query): ?int
    {
        if (empty($query)) {
            return null;
        }

        try {
            $url = "{$this->baseUrl}/api/v1/accounts/{$this->accountId}/contacts/search";
            $response = Http::withHeaders($this->getHeaders())->get($url, ['q' => $query]);

            if ($response->successful()) {
                $contacts = $response->json('payload') ?? [];
                if (! empty($contacts) && isset($contacts[0]['id'])) {
                    return (int) $contacts[0]['id'];
                }
            }
        } catch (\Throwable $e) {
            Log::error('Chatwoot searchContact failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Format phone to E.164 (+1XXXXXXXXXX).
     */
    protected function formatPhoneE164(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+' . $digits;
        } elseif (! empty($digits)) {
            return '+' . $digits;
        }

        return null;
    }

    /**
     * API request headers.
     */
    protected function getHeaders(): array
    {
        return [
            'api_access_token' => $this->apiToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
}
