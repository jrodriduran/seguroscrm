<?php

namespace Webkul\Communications\Services;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Communications\Models\CommunicationMessage;
use Webkul\Communications\Models\CommunicationTemplate;
use Webkul\Lead\Services\ChatwootService;

/**
 * Sends one template to one client through the first channel that works:
 * the template has text for it, the client allows it, and there is a way
 * to reach them (an email address, or an open chat conversation).
 */
class MessageDispatcher
{
    public function __construct(
        protected TemplateRenderer $renderer,
        protected ConsentRegistry $consents,
        protected ChatwootService $chatwoot,
        protected CommunicationSettings $settings,
    ) {}

    /**
     * Channel order for a step type: "preferred" tries the client's
     * preferred channel first, then the others.
     */
    public function channelsFor(string $type, int $personId): array
    {
        if ($type !== 'preferred') {
            return [$type];
        }

        $preferred = DB::table('persons')->where('id', $personId)->value('preferred_channel');
        $order = ['whatsapp', 'sms', 'email'];

        return in_array($preferred, $order, true) ? array_values(array_unique([$preferred, ...$order])) : $order;
    }

    /**
     * @return array{status: string, channel: ?string, detail: string}
     */
    public function send(int $personId, array $channels, CommunicationTemplate $template, array $context = []): array
    {
        $reasons = [];

        foreach ($channels as $channel) {
            if (! $this->consents->allows($personId, $channel, $template->purpose)) {
                $reasons[] = "{$channel}: ".trans('communications::app.sequences.reasons.no-consent');

                continue;
            }

            $rendered = $this->renderer->render($template, $channel, $personId, $context);

            if (! $rendered) {
                $reasons[] = "{$channel}: ".trans('communications::app.sequences.reasons.no-text');

                continue;
            }

            $result = $channel === 'email'
                ? $this->sendEmail($personId, $rendered, $template, $context)
                : $this->sendChat($personId, $channel, $rendered, $context);

            if ($result === true) {
                return ['status' => 'sent', 'channel' => $channel, 'detail' => Str::limit($rendered['subject'] ?? $rendered['body'], 120)];
            }

            $reasons[] = "{$channel}: {$result}";
        }

        return ['status' => 'skipped', 'channel' => null, 'detail' => Str::limit(implode(' · ', $reasons), 490)];
    }

    /**
     * @return true|string true when sent, else the reason
     */
    protected function sendEmail(int $personId, array $rendered, CommunicationTemplate $template, array $context)
    {
        $email = collect(json_decode((string) DB::table('persons')->where('id', $personId)->value('emails'), true))
            ->pluck('value')
            ->first(fn ($value) => filter_var($value, FILTER_VALIDATE_EMAIL));

        if (! $email) {
            return trans('communications::app.sequences.reasons.no-email');
        }

        $unsubscribe = URL::signedRoute('communications.unsubscribe', ['person' => $personId]);

        $html = view('communications::emails.message', [
            'body' => $rendered['html'],
            'agency' => $this->renderer->variables($personId, $context),
            'address' => $this->settings->get('agency.address'),
            'unsubscribe' => $template->purpose === 'marketing' ? $unsubscribe : null,
            'locale' => $rendered['locale'],
        ])->render();

        try {
            Mail::html($html, function (Message $message) use ($email, $rendered, $unsubscribe) {
                $message->to($email)->subject($rendered['subject'] ?: config('app.name'));
                $message->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$unsubscribe.'>');
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
        } catch (Throwable $e) {
            return Str::limit($e->getMessage(), 150);
        }

        $this->log($personId, 'mail', 'email', (string) Str::uuid(), trim(($rendered['subject'] ? $rendered['subject']."\n\n" : '').$rendered['body']), null, $context);

        return true;
    }

    /**
     * Chat channels answer inside an open conversation on that channel;
     * starting a new one needs provider templates (WhatsApp) — not yet.
     *
     * @return true|string
     */
    protected function sendChat(int $personId, string $channel, array $rendered, array $context)
    {
        if (! $this->chatwoot->isConfigured()) {
            return trans('communications::app.sequences.reasons.no-chatwoot');
        }

        $conversation = CommunicationMessage::where('person_id', $personId)
            ->where('provider', 'chatwoot')
            ->where('channel', $channel)
            ->whereNotNull('conversation_id')
            ->latest('sent_at')
            ->first(['conversation_id', 'inbox_id']);

        if (! $conversation) {
            return trans('communications::app.sequences.reasons.no-conversation');
        }

        $sent = $this->chatwoot->sendMessage((int) $conversation->conversation_id, $rendered['body']);

        if (! $sent || empty($sent['id'])) {
            return trans('communications::app.sequences.reasons.provider-failed');
        }

        $this->log($personId, 'chatwoot', $channel, (string) $sent['id'], $rendered['body'], $conversation, $context);

        return true;
    }

    protected function log(int $personId, string $provider, string $channel, string $externalId, string $content, $conversation, array $context): void
    {
        CommunicationMessage::firstOrCreate(['provider' => $provider, 'external_id' => $externalId], [
            'conversation_id' => $conversation?->conversation_id,
            'inbox_id' => $conversation?->inbox_id,
            'channel' => $channel,
            'direction' => 'out',
            'person_id' => $personId,
            'lead_id' => $context['lead_id'] ?? null,
            'sender_name' => '🤖 '.($context['sequence_name'] ?? trans('communications::app.sequences.title')),
            'content' => $content,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
