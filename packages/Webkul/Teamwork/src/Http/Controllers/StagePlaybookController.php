<?php

namespace Webkul\Teamwork\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Models\Sequence;
use Webkul\Communications\Models\Trigger;
use Webkul\Teamwork\Models\StageMilestone;
use Webkul\Teamwork\Models\StagePlaybook;
use Webkul\Teamwork\Services\MilestoneChecks;
use Webkul\Teamwork\Services\StagePlaybooks;

/**
 * Settings › Pipeline playbook: per stage, the milestones to complete, how
 * strict moving on is, what the team gets on arrival, and (from the
 * Communications module) the client sequences that start there.
 */
class StagePlaybookController extends Controller
{
    public function index(StagePlaybooks $playbooks): View
    {
        $pipelines = DB::table('lead_pipelines')->orderBy('id')->get(['id', 'name']);
        $pipelineId = (int) request('pipeline') ?: (int) ($pipelines->first()->id ?? 0);

        $stages = DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $pipelineId)->orderBy('sort_order')->get(['id', 'name', 'code']);

        $sequenceRules = class_exists('Webkul\\Communications\\Models\\Trigger')
            ? Trigger::with('sequence:id,name,is_active')
                ->whereIn('event', ['stage_entered', 'stage_idle'])
                ->get()
                ->groupBy(fn ($trigger) => (int) $trigger->option('stage_id'))
            : collect();

        $overdue = DB::table('teamwork_overdue_rules')->where('is_active', 1)->whereIn('lead_pipeline_stage_id', $stages->pluck('id'))->get(['lead_pipeline_stage_id', 'warning_hours', 'overdue_hours'])->keyBy('lead_pipeline_stage_id');

        return view('teamwork::playbook.index', [
            'pipelines' => $pipelines,
            'pipelineId' => $pipelineId,
            'stages' => $stages,
            'playbooks' => $stages->mapWithKeys(fn ($stage) => [$stage->id => StagePlaybook::forStage($stage->id)]),
            'milestones' => StageMilestone::whereIn('lead_pipeline_stage_id', $stages->pluck('id'))->orderBy('position')->get()->groupBy('lead_pipeline_stage_id'),
            'sequenceRules' => $sequenceRules,
            'sequences' => class_exists('Webkul\\Communications\\Models\\Sequence') ? Sequence::orderBy('name')->get(['id', 'name', 'is_active']) : collect(),
            'overdue' => $overdue,
            'checks' => MilestoneChecks::CHECKS,
            'users' => DB::table('users')->where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'stageName' => fn ($stage) => $playbooks->stageName($stage),
        ]);
    }

    public function update(Request $request, int $stageId): RedirectResponse
    {
        $stage = DB::table('lead_pipeline_stages')->where('id', $stageId)->first(['id', 'lead_pipeline_id']);

        abort_unless($stage, 404);

        $data = $request->validate([
            'gate' => ['required', Rule::in(StagePlaybook::GATES)],
            'entry_task_title' => ['nullable', 'string', 'max:180'],
            'entry_task_hours' => ['nullable', 'integer', 'between:1,720'],
            'entry_assign' => ['required', Rule::in(['owner', 'master', 'user'])],
            'entry_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'milestones' => ['nullable', 'array', 'max:20'],
            'milestones.*.name' => ['nullable', 'string', 'max:190'],
            'milestones.*.check' => ['nullable', Rule::in(MilestoneChecks::CHECKS)],
            'milestones.*.help' => ['nullable', 'string', 'max:300'],
        ]);

        DB::transaction(function () use ($request, $data, $stageId) {
            StagePlaybook::updateOrCreate(['lead_pipeline_stage_id' => $stageId], [
                'gate' => $data['gate'],
                'entry_task' => $request->boolean('entry_task'),
                'entry_task_title' => $data['entry_task_title'] ?? null,
                'entry_task_hours' => $data['entry_task_hours'] ?? 24,
                'entry_assign' => $data['entry_assign'],
                'entry_user_id' => $data['entry_user_id'] ?? null,
                'notify_owner' => $request->boolean('notify_owner'),
                'notify_master' => $request->boolean('notify_master'),
                'carry_over' => $request->boolean('carry_over'),
                'close_previous' => $request->boolean('close_previous'),
            ]);

            $keep = [];

            foreach (array_values($request->input('milestones', [])) as $index => $row) {
                $name = trim((string) ($row['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $values = [
                    'lead_pipeline_stage_id' => $stageId,
                    'position' => $index + 1,
                    'name' => $name,
                    'check' => ($row['check'] ?? '') ?: null,
                    'is_required' => ! empty($row['is_required']),
                    'help' => ($row['help'] ?? '') ?: null,
                ];

                $milestone = ! empty($row['id'])
                    ? StageMilestone::where('lead_pipeline_stage_id', $stageId)->find((int) $row['id'])
                    : null;

                $milestone ? $milestone->update($values) : $milestone = StageMilestone::create($values);
                $keep[] = $milestone->id;
            }

            StageMilestone::where('lead_pipeline_stage_id', $stageId)->whereNotIn('id', $keep ?: [0])->delete();
        });

        session()->flash('success', trans('teamwork::app.playbook.saved'));

        return redirect()->to(route('admin.settings.teamwork.playbook.index', ['pipeline' => $stage->lead_pipeline_id]).'#stage-'.$stageId);
    }
}
