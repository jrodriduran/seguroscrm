<?php

namespace Webkul\Admin\Http\Controllers\Chatwoot;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Activity\Models\Activity;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Services\ChatwootService;

class ChatwootController extends Controller
{
    public function __construct(
        protected ChatwootService $chatwootService
    ) {}

    /**
     * Get conversation status and message history for a lead.
     */
    public function getConversation(int $leadId): JsonResponse
    {
        $lead = Lead::with('person')->findOrFail($leadId);

        if (! $this->chatwootService->isConfigured()) {
            return response()->json([
                'success' => false,
                'configured' => false,
                'message' => 'La integración de Chatwoot no está configurada aún (falta CHATWOOT_API_TOKEN en el entorno).',
            ]);
        }

        $conversationId = $lead->chatwoot_conversation_id;

        // If no conversation exists yet, try to initialize contact and start conversation
        if (! $conversationId && $lead->person) {
            $contactId = $this->chatwootService->findOrCreateContact($lead->person, $lead);
            if ($contactId) {
                $conv = $this->chatwootService->createConversation(
                    $contactId,
                    "Conversación inicial sincronizada desde Krayin CRM para el lead #{$lead->id} ({$lead->title})"
                );

                if ($conv && isset($conv['id'])) {
                    $conversationId = $conv['id'];
                    $lead->update([
                        'chatwoot_conversation_id' => $conversationId,
                        'chatwoot_inbox_id' => $conv['inbox_id'] ?? null,
                    ]);
                }
            }
        }

        $messages = [];
        $conversationUrl = null;

        if ($conversationId) {
            $messages = $this->chatwootService->getMessages($conversationId);
            $conversationUrl = $this->chatwootService->getConversationUrl($conversationId);
        }

        return response()->json([
            'success' => true,
            'configured' => true,
            'conversation_id' => $conversationId,
            'conversation_url' => $conversationUrl,
            'messages' => $messages,
            'last_message_at' => $lead->chatwoot_last_message_at?->toIso8601String(),
        ]);
    }

    /**
     * Send an outbound message from CRM to customer via Chatwoot (WhatsApp / SMS).
     */
    public function sendMessage(Request $request, int $leadId): JsonResponse
    {
        $lead = Lead::with('person')->findOrFail($leadId);

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $content = $request->input('message');

        if (! $this->chatwootService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Chatwoot no está configurado.',
            ], 422);
        }

        $conversationId = $lead->chatwoot_conversation_id;

        // Ensure conversation is ready
        if (! $conversationId && $lead->person) {
            $contactId = $this->chatwootService->findOrCreateContact($lead->person, $lead);
            if ($contactId) {
                $conv = $this->chatwootService->createConversation($contactId, $content);
                if ($conv && isset($conv['id'])) {
                    $conversationId = $conv['id'];
                    $lead->update([
                        'chatwoot_conversation_id' => $conversationId,
                        'chatwoot_inbox_id' => $conv['inbox_id'] ?? null,
                        'chatwoot_last_message_at' => Carbon::now(),
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Mensaje enviado y conversación creada con éxito.',
                        'conversation_id' => $conversationId,
                    ]);
                }
            }
        }

        if (! $conversationId) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo vincular una conversación en Chatwoot para este cliente.',
            ], 422);
        }

        $res = $this->chatwootService->sendMessage($conversationId, $content);

        $lead->update(['chatwoot_last_message_at' => Carbon::now()]);

        // Record in CRM activities
        try {
            $activity = Activity::create([
                'title' => "📤 Mensaje Saliente Chatwoot a {$lead->person?->name}",
                'type' => 'call',
                'comment' => "Mensaje enviado al asegurado vía Chatwoot (#{$conversationId}):\n\n\"{$content}\"",
                'schedule_from' => Carbon::now(),
                'schedule_to' => Carbon::now(),
                'is_done' => 1,
                'user_id' => auth()->id() ?: 1,
            ]);

            $activity->leads()->attach($lead->id);

            if ($lead->person_id) {
                $activity->persons()->attach($lead->person_id);
            }
        } catch (\Throwable $e) {
        }

        return response()->json([
            'success' => true,
            'message' => 'Mensaje enviado exitosamente vía Chatwoot.',
            'payload' => $res,
        ]);
    }
}
