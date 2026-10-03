<?php

namespace Webkul\Teamwork\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Teamwork\Models\StageMilestone;
use Webkul\Teamwork\Services\StagePlaybooks;

/**
 * Milestones from the lead page: tick manual ones, and the agency owner's
 * "move anyway" when required milestones are missing.
 */
class LeadMilestoneController extends Controller
{
    public function __construct(protected StagePlaybooks $playbooks) {}

    public function toggle(Request $request, int $leadId, int $milestoneId): RedirectResponse
    {
        $lead = $this->authorizedLead($leadId);

        // Read-only roles (audit, front desk) can see milestones but not tick them.
        abort_unless(bouncer()->hasPermission('leads.edit'), 403);
        abort_unless(StageMilestone::whereKey($milestoneId)->exists(), 404);

        $this->playbooks->tick($lead->id, $milestoneId, $request->boolean('done'), $request->input('note'));

        return back();
    }

    public function override(Request $request, int $leadId, LeadRepository $leads): RedirectResponse
    {
        $lead = $this->authorizedLead($leadId);

        abort_unless($this->playbooks->isMaster(auth()->guard('user')->user()), 403);

        $data = $request->validate([
            'stage_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $stage = DB::table('lead_pipeline_stages')->where('id', $data['stage_id'])->where('lead_pipeline_id', $lead->lead_pipeline_id)->first(['id', 'code']);

        abort_unless($stage, 422);

        $gate = $this->playbooks->gate($lead, (int) $stage->id);

        DB::table('teamwork_stage_overrides')->insert([
            'lead_id' => $lead->id,
            'from_stage_id' => $lead->lead_pipeline_stage_id,
            'to_stage_id' => $stage->id,
            'user_id' => auth()->guard('user')->id(),
            'reason' => $data['reason'],
            'missing' => json_encode($gate['missing']),
            'created_at' => now(),
        ]);

        Event::dispatch('lead.update.before', $lead->id);

        $updated = $leads->update(array_filter([
            'entity_type' => 'leads',
            'lead_pipeline_stage_id' => $stage->id,
            'closed_at' => in_array($stage->code, ['won', 'lost'], true) ? now() : null,
        ]), $lead->id, ['lead_pipeline_stage_id']);

        Event::dispatch('lead.update.after', $updated);

        session()->flash('success', trans('teamwork::app.playbook.overridden'));

        return back();
    }

    /**
     * Same visibility rules as the rest of the CRM (all / group / own).
     */
    protected function authorizedLead(int $leadId): object
    {
        $lead = DB::table('leads')->where('id', $leadId)->first();

        abort_unless($lead, 404);

        $allowed = bouncer()->getAuthorizedUserIds();

        abort_if($allowed && ! in_array((int) $lead->user_id, array_map('intval', $allowed), true), 403);

        return $lead;
    }
}
