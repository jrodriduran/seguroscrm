<?php

namespace Webkul\Admin\Http\Controllers\Chatwoot;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Activity\Models\Activity;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;

class ChatwootWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from Chatwoot instance.
     */
    public function handle(Request $request): JsonResponse
    {
        $verifyToken = config('chatwoot.webhook_verify_token');
        if ($verifyToken && $request->header('X-Chatwoot-Signature') !== $verifyToken && $request->query('token') !== $verifyToken) {
            return response()->json(['error' => 'Unauthorized signature'], 401);
        }

        $event = $request->input('event');
        $payload = $request->all();

        if ($event === 'message_created') {
            $this->handleIncomingMessage($payload);
        }

        return response()->json(['status' => 'success', 'event' => $event]);
    }

    /**
     * Process incoming customer message from Chatwoot (WhatsApp, SMS, etc.).
     */
    protected function handleIncomingMessage(array $data): void
    {
        $messageType = $data['message_type'] ?? null;

        // Process only incoming messages from the customer
        if ($messageType !== 'incoming' && $messageType !== 0) {
            return;
        }

        $conversation = $data['conversation'] ?? [];
        $conversationId = $conversation['id'] ?? null;
        $inboxId = $conversation['inbox_id'] ?? null;
        $sender = $data['sender'] ?? [];
        $contactId = $sender['id'] ?? null;
        $senderName = $sender['name'] ?? 'Cliente WhatsApp';
        $senderPhone = $sender['phone_number'] ?? null;
        $senderEmail = $sender['email'] ?? null;
        $content = $data['content'] ?? '';

        if (! $conversationId) {
            return;
        }

        // 1. Locate Lead by chatwoot_conversation_id
        $lead = Lead::where('chatwoot_conversation_id', $conversationId)->first();

        // 2. If not found by conversation, locate Person by contact_id, phone or email
        if (! $lead) {
            $person = null;

            if ($contactId) {
                $person = Person::where('chatwoot_contact_id', $contactId)->first();
            }

            if (! $person && $senderPhone) {
                $cleanPhone = preg_replace('/\D/', '', $senderPhone);
                $person = Person::whereJsonContains('contact_numbers', ['value' => $cleanPhone])
                    ->orWhereJsonContains('contact_numbers', ['value' => $senderPhone])
                    ->first();
            }

            if (! $person && $senderEmail) {
                $person = Person::whereJsonContains('emails', ['value' => $senderEmail])->first();
            }

            // Create Person if not found
            if (! $person) {
                $person = Person::create([
                    'name' => $senderName,
                    'emails' => $senderEmail ? [['value' => $senderEmail, 'label' => 'work']] : [],
                    'contact_numbers' => $senderPhone ? [['value' => $senderPhone, 'label' => 'mobile']] : [],
                    'chatwoot_contact_id' => $contactId,
                ]);
            } else {
                if ($contactId && ! $person->chatwoot_contact_id) {
                    $person->update(['chatwoot_contact_id' => $contactId]);
                }
            }

            // Check if person has an active lead
            $lead = Lead::where('person_id', $person->id)->latest()->first();

            // If still no lead, create new Lead in the default pipeline
            if (! $lead) {
                $pipeline = Pipeline::where('is_default', 1)->first() ?: Pipeline::first();
                $stage = $pipeline ? Stage::where('lead_pipeline_id', $pipeline->id)->first() : null;

                $lead = Lead::create([
                    'title' => "Mensaje Entrante: {$senderName}",
                    'person_id' => $person->id,
                    'user_id' => 1,
                    'lead_pipeline_id' => $pipeline?->id ?: 1,
                    'lead_pipeline_stage_id' => $stage?->id ?: 1,
                    'chatwoot_conversation_id' => $conversationId,
                    'chatwoot_inbox_id' => $inboxId,
                ]);
            }
        }

        // Link conversation and timestamp
        $lead->update([
            'chatwoot_conversation_id' => $conversationId,
            'chatwoot_inbox_id' => $inboxId ?: $lead->chatwoot_inbox_id,
            'chatwoot_last_message_at' => Carbon::now(),
        ]);

        // Record interaction in CRM activity timeline
        try {
            Activity::create([
                'title' => "💬 Mensaje WhatsApp/Chatwoot de {$senderName}",
                'type' => 'call',
                'comment' => "Mensaje recibido vía Chatwoot (#{$conversationId}):\n\n\"{$content}\"",
                'schedule_from' => Carbon::now(),
                'schedule_to' => Carbon::now(),
                'is_done' => 1,
                'user_id' => $lead->user_id ?: 1,
                'lead_id' => $lead->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log Chatwoot message activity: '.$e->getMessage());
        }
    }
}
