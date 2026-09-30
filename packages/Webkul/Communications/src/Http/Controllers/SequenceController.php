<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Models\CallOutcome;
use Webkul\Communications\Models\CommunicationTemplate;
use Webkul\Communications\Models\Enrollment;
use Webkul\Communications\Models\Sequence;
use Webkul\Communications\Models\SequenceStep;
use Webkul\Communications\Models\Trigger;

class SequenceController extends Controller
{
    public function index(): View
    {
        return view('communications::sequences.index', [
            'sequences' => Sequence::withCount(['steps', 'enrollments as active_count' => fn ($query) => $query->where('status', Enrollment::ACTIVE)])
                ->with('triggers')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'stages' => $this->stageNames(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Sequence(['pause_on_reply' => true, 'exit_on_stage_change' => true]));
    }

    public function edit(int $id): View
    {
        return $this->form(Sequence::with(['steps', 'triggers'])->findOrFail($id));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $sequence = DB::transaction(function () use ($data, $request) {
            $sequence = Sequence::create($data['sequence'] + ['agency_id' => auth()->guard('user')->user()->agency_id]);
            $this->saveSteps($sequence, $data['steps']);
            $this->saveTriggers($sequence, $request);

            return $sequence;
        });

        session()->flash('success', trans('communications::app.sequences.saved'));

        return redirect()->route('admin.communications.sequences.edit', $sequence->id);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $sequence = Sequence::findOrFail($id);
        $data = $this->validated($request);

        DB::transaction(function () use ($sequence, $data, $request) {
            $sequence->update($data['sequence']);
            $this->saveSteps($sequence, $data['steps']);
            $this->saveTriggers($sequence, $request);
        });

        session()->flash('success', trans('communications::app.sequences.saved'));

        return redirect()->route('admin.communications.sequences.edit', $sequence->id);
    }

    public function toggle(int $id): RedirectResponse
    {
        $sequence = Sequence::findOrFail($id);
        $sequence->update(['is_active' => ! $sequence->is_active]);

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        Sequence::findOrFail($id)->delete();

        session()->flash('success', trans('communications::app.sequences.deleted'));

        return redirect()->route('admin.communications.sequences.index');
    }

    protected function form(Sequence $sequence): View
    {
        return view('communications::sequences.edit', [
            'sequence' => $sequence,
            'templates' => CommunicationTemplate::with('contents')->where('is_active', true)->orderBy('name')->get(),
            'users' => DB::table('users')->where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'tags' => DB::table('tags')->orderBy('name')->pluck('name', 'id'),
            'outcomes' => CallOutcome::forAgency(null, true),
            'stages' => $this->stageNames(),
            'enrollments' => $sequence->exists
                ? Enrollment::where('sequence_id', $sequence->id)->latest('id')->limit(30)->get()
                : collect(),
            'people' => $sequence->exists
                ? DB::table('persons')->whereIn('id', Enrollment::where('sequence_id', $sequence->id)->latest('id')->limit(30)->pluck('person_id'))->pluck('name', 'id')
                : collect(),
        ]);
    }

    protected function validated(Request $request): array
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'steps' => ['required', 'array', 'min:1', 'max:30'],
            'steps.*.type' => ['required', Rule::in([...SequenceStep::MESSAGE_TYPES, ...SequenceStep::ACTION_TYPES])],
            'steps.*.delay_days' => ['nullable', 'integer', 'between:0,365'],
            'steps.*.send_hour' => ['nullable', 'integer', 'between:0,23'],
            'steps.*.template_id' => ['nullable', 'integer', 'exists:communication_templates,id'],
            'steps.*.condition' => ['nullable', Rule::in(SequenceStep::CONDITIONS)],
            'steps.*.due_hours' => ['nullable', 'integer', 'between:1,720'],
            'steps.*.title' => ['nullable', 'string', 'max:180'],
            'steps.*.note' => ['nullable', 'string', 'max:1000'],
            'steps.*.cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $steps = collect($request->input('steps'))->values()->map(function ($step, $index) {
            $isMessage = in_array($step['type'], SequenceStep::MESSAGE_TYPES, true);

            return [
                'position' => $index + 1,
                'type' => $step['type'],
                'delay_days' => (int) ($step['delay_days'] ?? 0),
                'send_hour' => isset($step['send_hour']) && $step['send_hour'] !== '' ? (int) $step['send_hour'] : null,
                'template_id' => $isMessage ? ($step['template_id'] ?? null) : null,
                'condition' => $step['condition'] ?? 'always',
                'config' => $isMessage ? null : array_filter([
                    'title' => $step['title'] ?? null,
                    'note' => $step['note'] ?? null,
                    'due_hours' => $step['due_hours'] ?? null,
                    'assign_to' => $step['assign_to'] ?? 'owner',
                    'user_id' => $step['user_id'] ?? null,
                    'priority' => $step['priority'] ?? 'normal',
                    'item' => $step['item'] ?? null,
                    'cost' => $step['cost'] ?? null,
                    'tag_id' => $step['tag_id'] ?? null,
                ], fn ($value) => $value !== null && $value !== ''),
            ];
        })->all();

        return [
            'sequence' => [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'pause_on_reply' => $request->boolean('pause_on_reply'),
                'exit_on_stage_change' => $request->boolean('exit_on_stage_change'),
                'allow_reentry' => $request->boolean('allow_reentry'),
            ],
            'steps' => $steps,
        ];
    }

    /**
     * Replace the steps. Clients already inside keep their place by position.
     */
    protected function saveSteps(Sequence $sequence, array $steps): void
    {
        $existing = $sequence->steps()->get()->keyBy('position');

        foreach ($steps as $step) {
            if ($current = $existing->pull($step['position'])) {
                $current->update($step);
            } else {
                $sequence->steps()->create($step);
            }
        }

        // Steps removed from the end: clients waiting on them are done.
        foreach ($existing as $removed) {
            Enrollment::where('sequence_id', $sequence->id)->where('next_position', $removed->position)->whereIn('status', [Enrollment::ACTIVE, Enrollment::PAUSED])
                ->update(['status' => Enrollment::COMPLETED, 'next_run_at' => null, 'finished_at' => now()]);

            $removed->delete();
        }
    }

    /**
     * Event triggers set in the sequence (stage triggers live in Settings).
     */
    protected function saveTriggers(Sequence $sequence, Request $request): void
    {
        $sequence->triggers()->whereNotIn('event', Trigger::STAGE_EVENTS)->delete();

        foreach ((array) $request->input('triggers', []) as $trigger) {
            $event = $trigger['event'] ?? null;

            if (! in_array($event, Trigger::EVENTS, true) || in_array($event, Trigger::STAGE_EVENTS, true)) {
                continue;
            }

            $config = match ($event) {
                'policy_status' => ['status' => $trigger['status'] ?? 'grace_period_1'],
                'call_outcome' => ['outcome' => $trigger['outcome'] ?? null],
                'renewal_before' => ['days' => max(1, (int) ($trigger['days'] ?? 60))],
                default => [],
            };

            $sequence->triggers()->create(['event' => $event, 'config' => $config, 'is_active' => true]);
        }
    }

    /**
     * "Pipeline › Stage" names keyed by stage id.
     */
    protected function stageNames(): array
    {
        return DB::table('lead_pipeline_stages')
            ->join('lead_pipelines', 'lead_pipelines.id', '=', 'lead_pipeline_stages.lead_pipeline_id')
            ->orderBy('lead_pipelines.id')
            ->orderBy('lead_pipeline_stages.sort_order')
            ->get(['lead_pipeline_stages.id', 'lead_pipeline_stages.name', 'lead_pipelines.name as pipeline'])
            ->mapWithKeys(fn ($stage) => [$stage->id => $stage->pipeline.' › '.$stage->name])
            ->all();
    }
}
