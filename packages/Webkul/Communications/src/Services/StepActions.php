<?php

namespace Webkul\Communications\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Communications\Models\Enrollment;
use Webkul\Communications\Models\SequenceStep;

/**
 * Sequence steps that are work for the team instead of messages: call
 * tasks and physical deliveries become follow-ups (Teamwork), notices go
 * to the owner or the agency owner, tags go on the client.
 */
class StepActions
{
    public function __construct(protected TemplateRenderer $renderer) {}

    /**
     * @return array{status: string, channel: ?string, detail: string}
     */
    public function run(SequenceStep $step, Enrollment $enrollment, array $context): array
    {
        return match ($step->type) {
            'call_task', 'physical' => $this->followUp($step, $enrollment, $context),
            'notify' => $this->notify($step, $enrollment, $context),
            'tag' => $this->tag($step, $enrollment),
            default => ['status' => 'skipped', 'channel' => null, 'detail' => 'unknown step type'],
        };
    }

    protected function followUp(SequenceStep $step, Enrollment $enrollment, array $context): array
    {
        $followUp = 'Webkul\\Teamwork\\Models\\FollowUp';

        if (! class_exists($followUp)) {
            return ['status' => 'skipped', 'channel' => null, 'detail' => 'Teamwork not installed'];
        }

        $vars = $this->renderer->variables($enrollment->person_id, $context);
        $assignee = $this->assignee((string) $step->option('assign_to', 'owner'), $enrollment, $step->option('user_id'));

        if (! $assignee) {
            return ['status' => 'skipped', 'channel' => null, 'detail' => trans('communications::app.sequences.reasons.no-assignee')];
        }

        $default = $step->type === 'physical'
            ? trans('communications::app.sequences.defaults.physical', ['item' => trans('communications::app.sequences.items.'.$step->option('item', 'card')), 'name' => $vars['full_name']])
            : trans('communications::app.sequences.defaults.call', ['name' => $vars['full_name']]);

        $title = Str::limit($this->renderer->fill((string) ($step->option('title') ?: $default), $vars), 180, '');

        $note = collect([
            '🤖 '.$context['sequence_name'],
            $this->renderer->fill((string) $step->option('note', ''), $vars),
            $step->type === 'physical' ? $this->address($enrollment->person_id) : null,
            $step->type === 'physical' && $step->option('cost') ? trans('communications::app.sequences.cost', ['cost' => $step->option('cost')]) : null,
        ])->filter()->implode("\n");

        [$entityType, $entityId, $url] = $enrollment->lead_id
            ? ['lead', $enrollment->lead_id, route('admin.leads.view', $enrollment->lead_id, false)]
            : ['person', $enrollment->person_id, route('admin.contacts.persons.view', $enrollment->person_id, false)];

        $urgent = $step->option('priority') === 'urgent';
        $hours = max(1, (int) $step->option('due_hours', 24));

        $task = $followUp::create([
            'agency_id' => DB::table('users')->where('id', $assignee)->value('agency_id'),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'title' => $title,
            'url' => $url,
            'assigned_to' => $assignee,
            'created_by' => $assignee,
            'priority' => $urgent ? 'urgent' : 'normal',
            'note' => $note,
            'due_at' => now()->addHours($hours),
            'status' => 'open',
            'last_activity_at' => now(),
        ]);

        $this->notifier()?->notify($assignee, $urgent ? 'follow_up_urgent' : 'automation', $title, $step->option('note') ? $this->renderer->fill((string) $step->option('note'), $vars) : null,
            route('admin.teamwork.follow_ups.show', $task->id, false), $urgent);

        // Physical deliveries also go to the deliveries board (approval, tracking, spend).
        if ($step->type === 'physical') {
            app(Deliveries::class)->request($enrollment->person_id, (string) $step->option('item', 'card'), $this->renderer->fill((string) $step->option('note', ''), $vars) ?: null,
                $step->option('cost'), $assignee, ['lead_id' => $enrollment->lead_id, 'enrollment_id' => $enrollment->id, 'follow_up_id' => $task->id]);
        }

        return ['status' => 'task', 'channel' => null, 'detail' => $title];
    }

    protected function notify(SequenceStep $step, Enrollment $enrollment, array $context): array
    {
        $vars = $this->renderer->variables($enrollment->person_id, $context);
        $to = $step->option('assign_to', 'owner') === 'master' ? $this->masters() : array_filter([$this->assignee('owner', $enrollment)]);
        $message = $this->renderer->fill((string) ($step->option('note') ?: trans('communications::app.sequences.defaults.notify', ['name' => $vars['full_name'], 'sequence' => $context['sequence_name']])), $vars);

        if (! $to || ! $this->notifier()) {
            return ['status' => 'skipped', 'channel' => null, 'detail' => trans('communications::app.sequences.reasons.no-assignee')];
        }

        $this->notifier()->notify($to, 'automation', Str::limit($message, 180), '🤖 '.$context['sequence_name'],
            route('admin.contacts.persons.view', $enrollment->person_id, false), $step->option('priority') === 'urgent');

        return ['status' => 'notified', 'channel' => null, 'detail' => Str::limit($message, 180)];
    }

    protected function tag(SequenceStep $step, Enrollment $enrollment): array
    {
        $tagId = (int) $step->option('tag_id');

        if (! $tagId || ! DB::table('tags')->where('id', $tagId)->exists()) {
            return ['status' => 'skipped', 'channel' => null, 'detail' => 'tag not found'];
        }

        DB::table('person_tags')->insertOrIgnore(['tag_id' => $tagId, 'person_id' => $enrollment->person_id]);

        return ['status' => 'tagged', 'channel' => null, 'detail' => (string) DB::table('tags')->where('id', $tagId)->value('name')];
    }

    /**
     * The client's owner (lead, then contact), the agency owner, or a user.
     */
    protected function assignee(string $mode, Enrollment $enrollment, $userId = null): ?int
    {
        if ($mode === 'user' && $userId && DB::table('users')->where('id', $userId)->where('status', 1)->exists()) {
            return (int) $userId;
        }

        if ($mode === 'master') {
            return $this->masters()[0] ?? null;
        }

        $owner = ($enrollment->lead_id ? DB::table('leads')->where('id', $enrollment->lead_id)->value('user_id') : null)
            ?: DB::table('persons')->where('id', $enrollment->person_id)->value('user_id');

        return $owner ? (int) $owner : ($this->masters()[0] ?? null);
    }

    protected function masters(): array
    {
        return DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.permission_type', 'all')
            ->where('users.status', 1)
            ->orderBy('users.id')
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function address(int $personId): ?string
    {
        $raw = DB::table('attribute_values')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->where('attributes.entity_type', 'persons')
            ->where('attributes.code', 'address')
            ->where('attribute_values.entity_id', $personId)
            ->value('attribute_values.json_value');

        $address = json_decode((string) $raw, true);

        if (! $address || ! array_filter($address)) {
            return '⚠ '.trans('communications::app.sequences.no-address');
        }

        return '📍 '.trim(implode(', ', array_filter([$address['address'] ?? null, $address['city'] ?? null, trim(($address['state'] ?? '').' '.($address['postcode'] ?? ''))])));
    }

    protected function notifier()
    {
        $class = 'Webkul\\Teamwork\\Services\\Notifier';

        return class_exists($class) ? app($class) : null;
    }
}
