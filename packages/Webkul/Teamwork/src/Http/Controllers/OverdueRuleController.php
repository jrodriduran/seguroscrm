<?php

namespace Webkul\Teamwork\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Models\Pipeline;
use Webkul\Teamwork\Models\OverdueRule;
use Webkul\Teamwork\Services\BusinessHours;

class OverdueRuleController extends Controller
{
    /**
     * Rules list with an inline form.
     */
    public function index(BusinessHours $businessHours): View
    {
        $agencyId = auth()->guard('user')->user()->agency_id;

        return view('teamwork::rules.index', [
            'rules' => OverdueRule::with(['pipeline:id,name', 'stage:id,name'])
                ->when($agencyId, fn ($query) => $query->where(fn ($q) => $q->whereNull('agency_id')->orWhere('agency_id', $agencyId)))
                ->orderByRaw('lead_pipeline_id is null, lead_pipeline_id, lead_pipeline_stage_id is null, lead_pipeline_stage_id')
                ->get(),
            'pipelines' => Pipeline::with(['stages' => fn ($query) => $query->whereNotIn('code', ['won', 'lost'])->orderBy('sort_order')])->get(),
            'hoursPerDay' => $businessHours->hoursPerDay(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lead_pipeline_id' => ['nullable', 'integer', 'exists:lead_pipelines,id'],
            'lead_pipeline_stage_id' => ['nullable', 'integer', 'exists:lead_pipeline_stages,id'],
            'warning_hours' => ['required', 'integer', 'min:1', 'max:2000'],
            'overdue_hours' => ['required', 'integer', 'min:1', 'max:2000', 'gte:warning_hours'],
        ]);

        // A stage implies its pipeline.
        if (! empty($data['lead_pipeline_stage_id'])) {
            $data['lead_pipeline_id'] = DB::table('lead_pipeline_stages')->where('id', $data['lead_pipeline_stage_id'])->value('lead_pipeline_id');
        }

        OverdueRule::updateOrCreate([
            'agency_id' => auth()->guard('user')->user()->agency_id,
            'lead_pipeline_id' => $data['lead_pipeline_id'] ?? null,
            'lead_pipeline_stage_id' => $data['lead_pipeline_stage_id'] ?? null,
        ], [
            'warning_hours' => $data['warning_hours'],
            'overdue_hours' => $data['overdue_hours'],
            'is_active' => true,
        ]);

        session()->flash('success', trans('teamwork::app.rules.saved'));

        return redirect()->route('admin.settings.teamwork.rules.index');
    }

    public function toggle(int $id): RedirectResponse
    {
        $rule = $this->find($id);

        $rule->update(['is_active' => ! $rule->is_active]);

        return redirect()->route('admin.settings.teamwork.rules.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->find($id)->delete();

        session()->flash('success', trans('teamwork::app.rules.deleted'));

        return redirect()->route('admin.settings.teamwork.rules.index');
    }

    protected function find(int $id): OverdueRule
    {
        $rule = OverdueRule::findOrFail($id);

        $agencyId = auth()->guard('user')->user()->agency_id;

        abort_if($agencyId && $rule->agency_id !== $agencyId, 404);

        return $rule;
    }
}
