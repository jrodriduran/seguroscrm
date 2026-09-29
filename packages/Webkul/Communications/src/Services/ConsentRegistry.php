<?php

namespace Webkul\Communications\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Communications\Models\ContactConsent;

/**
 * Who may be contacted, through which channel, and why.
 *
 * - Transactional messages (policy issued, payment due) are allowed unless
 *   the client revoked that channel.
 * - Marketing messages (greetings, promotions) also need a recorded grant.
 */
class ConsentRegistry
{
    public const CHANNELS = ['email', 'sms', 'whatsapp', 'call'];

    public const PREFERRED = ['whatsapp', 'sms', 'email', 'call'];

    public const SOURCES = ['verbal', 'written', 'web_form', 'signed_consent', 'client_request', 'tcpa_lead', 'call_outcome', 'unsubscribe', 'sms_stop', 'import'];

    public const GRANTED = 'granted';

    public const REVOKED = 'revoked';

    /**
     * Latest consent row per channel, null where nothing was recorded.
     *
     * @return array<string, ContactConsent|null>
     */
    public function current(int $personId): array
    {
        $latest = ContactConsent::with('user:id,name')
            ->whereIn('id', DB::table('contact_consents')
                ->where('person_id', $personId)
                ->groupBy('channel')
                ->selectRaw('max(id)'))
            ->get()
            ->keyBy('channel');

        return collect(self::CHANNELS)->mapWithKeys(fn ($channel) => [$channel => $latest->get($channel)])->all();
    }

    /**
     * Whether a message of this purpose may go out on this channel.
     */
    public function allows(int $personId, string $channel, string $purpose = 'marketing'): bool
    {
        $status = $this->current($personId)[$channel]?->status;

        if ($status === self::REVOKED) {
            return false;
        }

        return $purpose === 'transactional' || $status === self::GRANTED;
    }

    /**
     * Record a grant or revocation on one or more channels.
     */
    public function record(int $personId, array $channels, string $status, string $source, ?string $note = null, ?int $userId = null): Collection
    {
        $userId ??= auth()->guard('user')->id();
        $current = $this->current($personId);

        $rows = collect($channels)
            ->intersect(self::CHANNELS)
            // Skip channels already in that state with no new note.
            ->reject(fn ($channel) => $current[$channel]?->status === $status && ! $note)
            ->map(fn ($channel) => ContactConsent::create([
                'person_id' => $personId,
                'channel' => $channel,
                'status' => $status,
                'source' => $source,
                'note' => $note,
                'user_id' => $userId,
                'created_at' => now(),
            ]))
            ->values();

        if ($rows->isNotEmpty()) {
            Event::dispatch('communications.consent.changed', [$personId, $rows]);
        }

        return $rows;
    }
}
