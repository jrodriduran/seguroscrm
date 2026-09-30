<?php

namespace Webkul\Communications\Services;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Communications\Models\Enrollment;
use Webkul\Communications\Models\Sequence;
use Webkul\Communications\Models\SequenceStep;
use Webkul\Communications\Models\StepRun;
use Webkul\Communications\Models\Trigger;

/**
 * Moves clients through communication sequences.
 *
 * Without overwhelming anyone:
 * - a client enters a sequence once per occasion (unless re-entry is on),
 * - leaving the stage that started a sequence ends it,
 * - a client reply pauses it and tells the owner,
 * - messages go out only during sending hours (agency time zone), and at
 *   most N automated messages per client per week — extra ones wait.
 */
class SequenceEngine
{
    public function __construct(
        protected MessageDispatcher $dispatcher,
        protected TemplateRenderer $renderer,
        protected CommunicationSettings $settings,
    ) {}

    // ── Entering ──────────────────────────────────────────────────────────

    /**
     * Start the sequences whose trigger matches. Returns enrollments created.
     */
    public function fire(string $event, array $match, int $personId, ?int $leadId = null, ?int $policyId = null, string $occasion = ''): int
    {
        $created = 0;

        $triggers = Trigger::with('sequence')
            ->where('event', $event)
            ->where('is_active', true)
            ->whereHas('sequence', fn ($query) => $query->where('is_active', true))
            ->get()
            ->filter(fn (Trigger $trigger) => collect($match)->every(fn ($value, $key) => (string) $trigger->option($key) === (string) $value));

        foreach ($triggers as $trigger) {
            $created += (int) (bool) $this->enroll($trigger->sequence, $personId, $leadId, $policyId, $trigger->id, $occasion ?: $event);
        }

        return $created;
    }

    public function enroll(Sequence $sequence, int $personId, ?int $leadId = null, ?int $policyId = null, ?int $triggerId = null, string $occasion = 'manual', ?int $userId = null): ?Enrollment
    {
        if (! DB::table('persons')->where('id', $personId)->exists() || ! $sequence->steps()->exists()) {
            return null;
        }

        $key = "s{$sequence->id}|p{$personId}|l{$leadId}|x{$policyId}|".Str::limit($occasion, 80, '');

        if ($sequence->allow_reentry) {
            $key .= '|'.now()->format('YmdHis').Str::random(4);
        }

        try {
            $enrollment = Enrollment::create([
                'sequence_id' => $sequence->id,
                'trigger_id' => $triggerId,
                'person_id' => $personId,
                'lead_id' => $leadId,
                'policy_id' => $policyId,
                'unique_key' => $key,
                'next_position' => (int) $sequence->steps()->min('position'),
                'next_run_at' => $this->timeForStep($sequence->steps()->first(), now()),
                'status' => Enrollment::ACTIVE,
                'enrolled_by' => $userId,
            ]);
        } catch (QueryException) {
            return null; // Already went through this sequence for this occasion.
        }

        // Steps due right away run now, even before the scheduler's next pass.
        if ($enrollment->next_run_at <= now()) {
            $this->advance($enrollment);
        }

        return $enrollment;
    }

    // ── Running ───────────────────────────────────────────────────────────

    /**
     * Run every step that is due. Returns how many steps ran.
     */
    public function runDue(int $limit = 200): int
    {
        $ran = 0;

        Enrollment::where('status', Enrollment::ACTIVE)
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get()
            ->each(function (Enrollment $enrollment) use (&$ran) {
                $ran += $this->advance($enrollment);
            });

        return $ran;
    }

    /**
     * Run the due steps of one enrollment (several if they share a day).
     */
    public function advance(Enrollment $enrollment): int
    {
        $ran = 0;
        $sequence = $enrollment->sequence()->with('steps.template.contents')->first();

        while ($enrollment->status === Enrollment::ACTIVE && $enrollment->next_run_at && $enrollment->next_run_at <= now()) {
            $step = $sequence?->steps->firstWhere('position', $enrollment->next_position);

            if (! $step) {
                $this->finish($enrollment, Enrollment::COMPLETED);

                break;
            }

            if ($step->isMessage() && ($wait = $this->messageWait($enrollment))) {
                $enrollment->update(['next_run_at' => $wait]);

                break;
            }

            $this->runStep($enrollment, $step, $sequence);
            $ran++;

            $next = $sequence->steps->first(fn ($candidate) => $candidate->position > $step->position);

            if (! $next) {
                $this->finish($enrollment, Enrollment::COMPLETED);

                break;
            }

            $enrollment->update([
                'next_position' => $next->position,
                'next_run_at' => $this->timeForStep($next, now()),
            ]);
        }

        return $ran;
    }

    protected function runStep(Enrollment $enrollment, SequenceStep $step, Sequence $sequence): void
    {
        $context = [
            'lead_id' => $enrollment->lead_id,
            'policy_id' => $enrollment->policy_id,
            'sequence_name' => $sequence->name,
        ];

        try {
            if ($step->condition === 'if_no_reply' && $this->clientReplied($enrollment)) {
                $result = ['status' => 'skipped', 'channel' => null, 'detail' => trans('communications::app.sequences.reasons.replied')];
            } elseif ($step->isMessage()) {
                $result = $step->template
                    ? $this->dispatcher->send($enrollment->person_id, $this->dispatcher->channelsFor($step->type, $enrollment->person_id), $step->template, $context)
                    : ['status' => 'skipped', 'channel' => null, 'detail' => trans('communications::app.sequences.reasons.no-template')];
            } else {
                $result = app(StepActions::class)->run($step, $enrollment, $context);
            }
        } catch (Throwable $e) {
            Log::error("Sequence step {$step->id} failed for enrollment {$enrollment->id}: ".$e->getMessage());

            $result = ['status' => 'failed', 'channel' => null, 'detail' => Str::limit($e->getMessage(), 300)];
        }

        StepRun::create([
            'enrollment_id' => $enrollment->id,
            'step_id' => $step->id,
            'status' => $result['status'],
            'channel' => $result['channel'] ?? null,
            'detail' => $result['detail'] ?? null,
            'created_at' => now(),
        ]);
    }

    // ── Leaving ───────────────────────────────────────────────────────────

    public function finish(Enrollment $enrollment, string $status, ?string $reason = null): void
    {
        $enrollment->update(['status' => $status, 'exit_reason' => $reason, 'next_run_at' => null, 'finished_at' => now()]);
    }

    public function pause(Enrollment $enrollment, string $reason): void
    {
        if ($enrollment->status === Enrollment::ACTIVE) {
            $enrollment->update(['status' => Enrollment::PAUSED, 'exit_reason' => $reason]);
        }
    }

    public function resume(Enrollment $enrollment): void
    {
        if ($enrollment->status === Enrollment::PAUSED) {
            $enrollment->update(['status' => Enrollment::ACTIVE, 'exit_reason' => null, 'next_run_at' => max(now(), $enrollment->next_run_at ?? now())]);
        }
    }

    /**
     * A lead left a stage: end the sequences that stage started.
     */
    public function stageLeft(int $leadId, int $stageId): void
    {
        Enrollment::with(['sequence', 'trigger'])
            ->where('lead_id', $leadId)
            ->whereIn('status', [Enrollment::ACTIVE, Enrollment::PAUSED])
            ->get()
            ->filter(fn (Enrollment $enrollment) => $enrollment->sequence?->exit_on_stage_change
                && in_array($enrollment->trigger?->event, Trigger::STAGE_EVENTS, true)
                && (int) $enrollment->trigger->option('stage_id') === $stageId)
            ->each(fn (Enrollment $enrollment) => $this->finish($enrollment, Enrollment::EXITED, 'stage_changed'));
    }

    /**
     * The client wrote in: pause what should not keep talking over them.
     */
    public function clientWrote(int $personId): array
    {
        return Enrollment::with('sequence')
            ->where('person_id', $personId)
            ->where('status', Enrollment::ACTIVE)
            ->get()
            ->filter(fn (Enrollment $enrollment) => $enrollment->sequence?->pause_on_reply)
            ->each(fn (Enrollment $enrollment) => $this->pause($enrollment, 'client_replied'))
            ->values()
            ->all();
    }

    // ── Timing ────────────────────────────────────────────────────────────

    /**
     * When a step runs: its delay after $from, at its hour (agency time).
     */
    public function timeForStep(?SequenceStep $step, Carbon $from): Carbon
    {
        if (! $step || ((int) $step->delay_days === 0 && $step->send_hour === null)) {
            return $from->copy();
        }

        $local = $from->copy()->setTimezone($this->timezone())->addDays((int) $step->delay_days);

        if ($step->send_hour !== null) {
            $local->setTime((int) $step->send_hour, 0);

            if ((int) $step->delay_days === 0 && $local->lt($from)) {
                $local->addDay();
            }
        }

        return $local->setTimezone(config('app.timezone'));
    }

    /**
     * Null when a message may go now; otherwise when it may.
     */
    protected function messageWait(Enrollment $enrollment): ?Carbon
    {
        $zone = $this->timezone();
        $local = now()->setTimezone($zone);
        $from = (int) $this->settings->get('sequences.send_from', 9);
        $until = (int) $this->settings->get('sequences.send_until', 19);

        if ($local->hour < $from) {
            return $local->copy()->setTime($from, 0)->setTimezone(config('app.timezone'));
        }

        if ($local->hour >= $until) {
            return $local->copy()->addDay()->setTime($from, 0)->setTimezone(config('app.timezone'));
        }

        $cap = (int) $this->settings->get('sequences.weekly_cap', 3);

        $sentThisWeek = DB::table('communication_step_runs')
            ->join('communication_enrollments', 'communication_enrollments.id', '=', 'communication_step_runs.enrollment_id')
            ->where('communication_enrollments.person_id', $enrollment->person_id)
            ->where('communication_step_runs.status', 'sent')
            ->whereNotNull('communication_step_runs.channel')
            ->where('communication_step_runs.created_at', '>=', now()->subDays(7))
            ->count();

        return $cap > 0 && $sentThisWeek >= $cap ? now()->addDay() : null;
    }

    protected function clientReplied(Enrollment $enrollment): bool
    {
        return DB::table('communication_messages')
            ->where('person_id', $enrollment->person_id)
            ->where('direction', 'in')
            ->where('sent_at', '>=', $enrollment->created_at)
            ->exists();
    }

    public function timezone(): string
    {
        $zone = null;

        try {
            $zone = core()->getConfigData('general.general.timezone.timezone');
        } catch (Throwable) {
        }

        return $zone && in_array($zone, timezone_identifiers_list(), true) ? $zone : config('app.timezone');
    }
}
