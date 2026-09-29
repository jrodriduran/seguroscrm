<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Communications\Services\ChatwootInbound;
use Webkul\Communications\Services\CommunicationSettings;

/**
 * Receives Chatwoot events. The URL carries a secret token only Chatwoot
 * knows, and events from other Chatwoot accounts are ignored.
 */
class ChatwootWebhookController extends Controller
{
    public function handle(Request $request, CommunicationSettings $settings, ChatwootInbound $inbound): JsonResponse
    {
        $token = $settings->get('chatwoot.webhook_token') ?: config('chatwoot.webhook_verify_token');

        if (! $token || ! hash_equals((string) $token, (string) $request->query('token', $request->header('X-Chatwoot-Token', '')))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $accountId = $request->input('account.id');

        if ($accountId && (string) $accountId !== (string) config('chatwoot.account_id')) {
            return response()->json(['status' => 'ignored', 'reason' => 'account']);
        }

        try {
            $result = $inbound->handle($request->all());
        } catch (Throwable $e) {
            Log::error('Chatwoot webhook failed: '.$e->getMessage(), ['event' => $request->input('event'), 'id' => $request->input('id')]);

            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => $result]);
    }
}
