<?php

namespace Webkul\Admin\Http\Controllers\Chatwoot;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Activity\Models\Activity;
use Webkul\Lead\Exceptions\TcpaConsentRequiredException;
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
                'has_tcpa_consent' => (bool) $lead->has_tcpa_consent,
                'tcpa_consented_at' => $lead->tcpa_consented_at?->toIso8601String(),
                'tcpa_consent_type' => $lead->tcpa_consent_type,
                'tcpa_consent_proof' => $lead->tcpa_consent_proof,
            ]);
        }

        $conversationId = $lead->chatwoot_conversation_id;

        // If no conversation exists yet, try to initialize contact and start conversation (only if TCPA consent exists)
        if (! $conversationId && $lead->person && $lead->hasTcpaConsent()) {
            $contactId = $this->chatwootService->findOrCreateContact($lead->person, $lead);
            if ($contactId) {
                try {
                    $conv = $this->chatwootService->createConversation(
                        $contactId,
                        "Conversación inicial sincronizada desde Krayin CRM para el lead #{$lead->id} ({$lead->title})",
                        null,
                        $lead
                    );

                    if ($conv && isset($conv['id'])) {
                        $conversationId = $conv['id'];
                        $lead->update([
                            'chatwoot_conversation_id' => $conversationId,
                            'chatwoot_inbox_id' => $conv['inbox_id'] ?? null,
                        ]);
                    }
                } catch (\Throwable $e) {
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
            'has_tcpa_consent' => (bool) $lead->has_tcpa_consent,
            'tcpa_consented_at' => $lead->tcpa_consented_at?->toIso8601String(),
            'tcpa_consent_type' => $lead->tcpa_consent_type,
            'tcpa_consent_proof' => $lead->tcpa_consent_proof,
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

        // TCPA Compliance Gate (47 U.S.C. § 227)
        if (! $lead->hasTcpaConsent()) {
            return response()->json([
                'success' => false,
                'tcpa_violation' => true,
                'message' => trans('admin::insurance.chatwoot.tcpa_consent_required_error'),
            ], 422);
        }

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
                try {
                    $conv = $this->chatwootService->createConversation($contactId, $content, null, $lead);
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
                } catch (TcpaConsentRequiredException $e) {
                    return response()->json([
                        'success' => false,
                        'tcpa_violation' => true,
                        'message' => $e->getMessage(),
                    ], 422);
                }
            }
        }

        if (! $conversationId) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo vincular una conversación en Chatwoot para este cliente.',
            ], 422);
        }

        try {
            $res = $this->chatwootService->sendMessage($conversationId, $content, $lead);
        } catch (TcpaConsentRequiredException $e) {
            return response()->json([
                'success' => false,
                'tcpa_violation' => true,
                'message' => $e->getMessage(),
            ], 422);
        }

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

    /**
     * Record express TCPA consent for a lead.
     */
    public function recordTcpaConsent(Request $request, int $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $request->validate([
            'consent_type' => 'required|string|in:web_form_optin,inbound_call_verbal,signed_consent_doc,sms_optin_keyword',
            'consent_proof' => 'required|string|max:255',
        ]);

        $lead->update([
            'has_tcpa_consent' => true,
            'tcpa_consented_at' => Carbon::now(),
            'tcpa_consent_type' => $request->input('consent_type'),
            'tcpa_consent_proof' => $request->input('consent_proof'),
        ]);

        // Create activity audit
        try {
            $activity = Activity::create([
                'title' => '🛡️ TCPA Consentimiento Expreso Registrado (47 U.S.C. § 227)',
                'type' => 'note',
                'comment' => sprintf(
                    "Consentimiento expreso TCPA verificado y registrado.\nTipo: %s\nEvidencia / Comprobante: %s\nRegistrado por: %s",
                    $request->input('consent_type'),
                    $request->input('consent_proof'),
                    auth()->user()?->name ?? 'Sistema CRM'
                ),
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
            'message' => trans('admin::insurance.chatwoot.tcpa_consent_recorded_success'),
            'lead' => [
                'has_tcpa_consent' => true,
                'tcpa_consented_at' => $lead->tcpa_consented_at?->toIso8601String(),
                'tcpa_consent_type' => $lead->tcpa_consent_type,
                'tcpa_consent_proof' => $lead->tcpa_consent_proof,
            ],
        ]);
    }
}
