<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Models\Sequence;
use Webkul\Communications\Models\Trigger;

/**
 * Settings › Communications by stage: which sequences start when a lead
 * enters a stage, or has been in it for some days. Stages with nothing
 * assigned send nothing — that is how clients are not overwhelmed.
 */
class StageRuleController extends Controller
{
    public function index(): View
    {
        $rules = Trigger::with('sequence:id,name,is_active')
            ->whereIn('event', Trigger::STAGE_EVENTS)
            ->get()
            ->groupBy(fn (Trigger $trigger) => (int) $trigger->option('stage_id'));

        return view('communications::settings.stage-rules', [
            'pipelines' => DB::table('lead_pipelines')->orderBy('id')->get(['id', 'name'])->map(fn ($pipeline) => (object) [
                'id' => $pipeline->id,
                'name' => $pipeline->name,
                'stages' => DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $pipeline->id)->orderBy('sort_order')->get(['id', 'name', 'code']),
            ]),
            'rules' => $rules,
            'sequences' => Sequence::orderBy('name')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stage_id' => ['required', 'integer', 'exists:lead_pipeline_stages,id'],
            'sequence_id' => ['required', 'integer', 'exists:communication_sequences,id'],
            'moment' => ['required', Rule::in(Trigger::STAGE_EVENTS)],
            'days' => ['nullable', 'required_if:moment,stage_idle', 'integer', 'between:1,365'],
        ]);

        Trigger::create([
            'sequence_id' => $data['sequence_id'],
            'event' => $data['moment'],
            'config' => array_filter(['stage_id' => (int) $data['stage_id'], 'days' => $data['moment'] === 'stage_idle' ? (int) $data['days'] : null]),
            'is_active' => true,
        ]);

        session()->flash('success', trans('communications::app.stages.saved'));

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        Trigger::whereIn('event', Trigger::STAGE_EVENTS)->findOrFail($id)->delete();

        session()->flash('success', trans('communications::app.stages.deleted'));

        return back();
    }
}
