<?php

namespace Webkul\Communications\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Communications\Models\Campaign;
use Webkul\Communications\Models\CampaignRecipient;

/**
 * Sends campaigns: freezes the recipient list when a campaign is scheduled,
 * then sends a batch on every scheduler pass, only within sending hours.
 */
class CampaignRunner
{
    public const BATCH = 60;

    public function __construct(
        protected AudienceQuery $audiences,
        protected MessageDispatcher $dispatcher,
        protected CommunicationSettings $settings,
        protected SequenceEngine $engine,
    ) {}

    /**
     * Freeze recipients and set the campaign to go out at its date.
     */
    public function schedule(Campaign $campaign, int $userId): int
    {
        $rules = $campaign->audience?->rules ?? [];

        DB::transaction(function () use ($campaign, $rules) {
            $campaign->recipients()->delete();

            $this->audiences->query($rules)->orderBy('persons.id')->chunk(500, function ($people) use ($campaign) {
                DB::table('communication_campaign_recipients')->insertOrIgnore($people->map(fn ($person) => [
                    'campaign_id' => $campaign->id,
                    'person_id' => $person->id,
                    'status' => 'pending',
                ])->all());
            });
        });

        $campaign->update([
            'status' => Campaign::SCHEDULED,
            'approved_by' => $userId,
            'scheduled_at' => $campaign->scheduled_at ?? now(),
        ]);

        return $campaign->recipients()->count();
    }

    public function cancel(Campaign $campaign): void
    {
        $campaign->recipients()->where('status', 'pending')->update(['status' => 'cancelled']);
        $campaign->update(['status' => Campaign::CANCELLED, 'finished_at' => now()]);
    }

    /**
     * One pass of the scheduler. Returns how many messages were attempted.
     */
    public function runDue(): int
    {
        if (! $this->withinSendingHours()) {
            return 0;
        }

        $attempted = 0;

        $campaigns = Campaign::with('template.contents')
            ->whereIn('status', [Campaign::SCHEDULED, Campaign::SENDING])
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->get();

        foreach ($campaigns as $campaign) {
            if ($campaign->status === Campaign::SCHEDULED) {
                $campaign->update(['status' => Campaign::SENDING, 'started_at' => now()]);
            }

            if (! $campaign->template) {
                $this->finishPending($campaign, 'skipped', trans('communications::app.sequences.reasons.no-template'));

                continue;
            }

            $batch = $campaign->recipients()->where('status', 'pending')->limit(self::BATCH)->get();

            foreach ($batch as $recipient) {
                $attempted++;
                $this->sendOne($campaign, $recipient);
            }

            if (! $campaign->recipients()->where('status', 'pending')->exists()) {
                $campaign->update(['status' => Campaign::SENT, 'finished_at' => now()]);
            }
        }

        return $attempted;
    }

    public function stats(Campaign $campaign): array
    {
        $counts = $campaign->recipients()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'sent' => (int) ($counts['sent'] ?? 0),
            'skipped' => (int) ($counts['skipped'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
            'by_channel' => $campaign->recipients()->where('status', 'sent')->selectRaw('channel, count(*) as total')->groupBy('channel')->pluck('total', 'channel')->all(),
        ];
    }

    protected function sendOne(Campaign $campaign, CampaignRecipient $recipient): void
    {
        try {
            $result = $this->dispatcher->send(
                (int) $recipient->person_id,
                $this->dispatcher->channelsFor($campaign->channel, (int) $recipient->person_id),
                $campaign->template,
                ['sequence_name' => $campaign->name]
            );
        } catch (Throwable $e) {
            $result = ['status' => 'failed', 'channel' => null, 'detail' => Str::limit($e->getMessage(), 300)];
        }

        $recipient->update([
            'status' => $result['status'] === 'sent' ? 'sent' : ($result['status'] === 'failed' ? 'failed' : 'skipped'),
            'channel' => $result['channel'],
            'detail' => Str::limit((string) $result['detail'], 490),
            'sent_at' => now(),
        ]);
    }

    protected function finishPending(Campaign $campaign, string $status, string $detail): void
    {
        $campaign->recipients()->where('status', 'pending')->update(['status' => $status, 'detail' => $detail, 'sent_at' => now()]);
        $campaign->update(['status' => Campaign::SENT, 'finished_at' => now()]);
    }

    protected function withinSendingHours(): bool
    {
        $hour = now()->setTimezone($this->engine->timezone())->hour;

        return $hour >= (int) $this->settings->get('sequences.send_from', 9) && $hour < (int) $this->settings->get('sequences.send_until', 19);
    }
}
