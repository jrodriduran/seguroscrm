<?php

namespace Webkul\Admin\Http\Controllers\Lead;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Lead\TeamRadarDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Repositories\AssignmentRuleRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SlaRuleRepository;
use Webkul\Lead\Repositories\TypeRepository;
use Webkul\Lead\Services\SlaEscalationService;
use Webkul\User\Repositories\UserRepository;

class TeamRadarController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected LeadRepository $leadRepository,
        protected UserRepository $userRepository,
        protected PipelineRepository $pipelineRepository,
        protected TypeRepository $typeRepository,
        protected AssignmentRuleRepository $assignmentRuleRepository,
        protected SlaRuleRepository $slaRuleRepository,
        protected SlaEscalationService $escalationService
    ) {}

    /**
     * Master Agent control tower view.
     */
    public function index(): View
    {
        $pipelines = $this->pipelineRepository->all();
        $selectedPipelineId = (int) request('pipeline_id', $pipelines->first()?->id ?? 1);

        $assignmentRule = $this->assignmentRuleRepository->getRuleForPipeline($selectedPipelineId);
        $slaRules = $this->slaRuleRepository->findWhere(['lead_pipeline_id' => $selectedPipelineId]);
        $leadTypes = $this->typeRepository->all();

        // Workload & capacity metrics per agent
        $users = $this->userRepository->findWhere(['status' => 1]);
        $userStats = DB::table('leads')
            ->select(
                'user_id',
                DB::raw("COUNT(CASE WHEN sla_status IN ('pending', 'active', 'overdue', 'escalated') THEN 1 END) as active_leads"),
                DB::raw("COUNT(CASE WHEN sla_status = 'pending' THEN 1 END) as pending_leads"),
                DB::raw("COUNT(CASE WHEN sla_status = 'overdue' THEN 1 END) as overdue_leads"),
                DB::raw("COUNT(CASE WHEN sla_status = 'escalated' THEN 1 END) as escalated_leads")
            )
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $maxCapacity = (int) ($assignmentRule->max_capacity ?? 0);

        $agents = $users->map(function ($u) use ($userStats, $maxCapacity) {
            $stats = $userStats->get($u->id);
            $u->active_leads    = (int) ($stats->active_leads ?? 0);
            $u->pending_leads   = (int) ($stats->pending_leads ?? 0);
            $u->overdue_leads   = (int) ($stats->overdue_leads ?? 0);
            $u->escalated_leads = (int) ($stats->escalated_leads ?? 0);
            $u->max_capacity    = $maxCapacity;
            $u->capacity_percent = $maxCapacity > 0 ? min(100, (int) round(($u->active_leads / $maxCapacity) * 100)) : 0;

            return $u;
        });

        // Leads escalated to Master Agent
        $escalatedLeads = $this->leadRepository
            ->scopes(['escalated'])
            ->with(['user', 'person', 'pipeline'])
            ->orderBy('escalated_at', 'desc')
            ->get();

        // Unassigned leads waiting for manual triage or routing
        $unassignedLeads = $this->leadRepository
            ->scopes(['unassigned'])
            ->with(['person', 'pipeline', 'stage'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin::leads.team-radar', [
            'agents'             => $agents,
            'pipelines'          => $pipelines,
            'selectedPipelineId' => $selectedPipelineId,
            'assignmentRule'     => $assignmentRule,
            'slaRules'           => $slaRules,
            'leadTypes'          => $leadTypes,
            'escalatedLeads'     => $escalatedLeads,
            'unassignedLeads'    => $unassignedLeads,
        ]);
    }

    /**
     * Render the TeamRadarDataGrid via AJAX.
     */
    public function dataGrid(TeamRadarDataGrid $dataGrid): mixed
    {
        return $dataGrid->toJson();
    }

    /**
     * Agent "My Pending" view — their prioritised task queue.
     */
    public function myPending(): View
    {
        $userId = auth()->id();

        $overdue = $this->leadRepository
            ->scopes(['overdue'])
            ->where('user_id', $userId)
            ->with(['person', 'stage', 'activities' => fn ($q) => $q->where('is_done', false)->orderBy('priority', 'desc')])
            ->orderBy('assigned_at')
            ->limit(50)
            ->get();

        $escalated = $this->leadRepository
            ->scopes(['escalated'])
            ->where('user_id', $userId)
            ->with(['person', 'stage'])
            ->orderBy('escalated_at', 'desc')
            ->limit(50)
            ->get();

        $urgent = \Webkul\Activity\Models\ActivityProxy::modelClass()::query()
            ->urgent()
            ->pending()
            ->whereHas('leads', fn ($q) => $q->where('user_id', $userId))
            ->with('leads')
            ->orderBy('schedule_from')
            ->limit(50)
            ->get();

        $normal = \Webkul\Activity\Models\ActivityProxy::modelClass()::query()
            ->pending()
            ->where('priority', 'normal')
            ->whereHas('leads', fn ($q) => $q->where('user_id', $userId))
            ->with('leads')
            ->orderBy('schedule_from')
            ->limit(50)
            ->get();

        // Agent capacity
        $activeLeadsCount = $this->leadRepository
            ->where('user_id', $userId)
            ->whereIn('sla_status', ['pending', 'active', 'overdue', 'escalated'])
            ->count();

        return view('admin::leads.my-pending', compact('overdue', 'escalated', 'urgent', 'normal', 'activeLeadsCount'));
    }

    /**
     * Save pipeline assignment strategy & agent pool (Master Agent control).
     */
    public function saveAssignmentRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_pipeline_id' => ['required', 'integer', 'exists:lead_pipelines,id'],
            'strategy'         => ['required', 'string', 'in:round_robin,least_loaded,manual'],
            'max_capacity'     => ['required', 'integer', 'min:0'],
            'agent_ids'        => ['nullable', 'array'],
            'agent_ids.*'      => ['integer', 'exists:users,id'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        $existing = $this->assignmentRuleRepository->findOneWhere([
            'lead_pipeline_id' => $data['lead_pipeline_id'],
        ]);

        if ($existing) {
            $this->assignmentRuleRepository->update([
                'strategy'     => $data['strategy'],
                'max_capacity' => $data['max_capacity'],
                'agent_ids'    => $data['agent_ids'] ?? [],
                'is_active'    => $data['is_active'] ?? true,
            ], $existing->id);
        } else {
            $this->assignmentRuleRepository->create([
                'lead_pipeline_id' => $data['lead_pipeline_id'],
                'strategy'         => $data['strategy'],
                'max_capacity'     => $data['max_capacity'],
                'agent_ids'        => $data['agent_ids'] ?? [],
                'is_active'        => $data['is_active'] ?? true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => trans('admin::insurance.team_radar.assignment_saved'),
        ]);
    }

    /**
     * Save or update SLA rule for pipeline & lead type.
     */
    public function saveSlaRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_pipeline_id'    => ['required', 'integer', 'exists:lead_pipelines,id'],
            'lead_type_id'        => ['nullable', 'integer', 'exists:lead_types,id'],
            'first_contact_hours' => ['required', 'integer', 'min:1'],
            'follow_up_hours'     => ['required', 'integer', 'min:1'],
            'escalation_hours'    => ['required', 'integer', 'min:1'],
        ]);

        $existing = $this->slaRuleRepository->findOneWhere([
            'lead_pipeline_id' => $data['lead_pipeline_id'],
            'lead_type_id'     => $data['lead_type_id'] ?? null,
        ]);

        if ($existing) {
            $this->slaRuleRepository->update($data, $existing->id);
        } else {
            $this->slaRuleRepository->create($data);
        }

        return response()->json([
            'success' => true,
            'message' => trans('admin::insurance.team_radar.sla_rule_saved'),
        ]);
    }

    /**
     * Manual 1-on-1 assign of lead to agent (from unassigned or reassign).
     */
    public function assignManual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id'     => ['required', 'integer', 'exists:leads,id'],
            'new_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $lead = $this->leadRepository->find($data['lead_id']);

        if ($lead) {
            $this->leadRepository->update([
                'user_id'           => $data['new_user_id'],
                'assigned_at'       => now(),
                'sla_status'        => 'pending',
                'escalated_at'      => null,
                'escalation_reason' => null,
            ], $lead->id);

            event('lead.update.after', $lead->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => trans('admin::insurance.team_radar.lead_assigned_success'),
        ]);
    }

    /**
     * Bulk-reassign selected leads to a different agent.
     */
    public function bulkReassign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_ids'    => ['required', 'array', 'min:1'],
            'lead_ids.*'  => ['integer', 'exists:leads,id'],
            'new_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        foreach ($data['lead_ids'] as $leadId) {
            $lead = $this->leadRepository->find($leadId);

            if ($lead) {
                $this->leadRepository->update([
                    'user_id'           => $data['new_user_id'],
                    'assigned_at'       => now(),
                    'sla_status'        => 'pending',
                    'escalated_at'      => null,
                    'escalation_reason' => null,
                ], $leadId);

                event('lead.update.after', $lead->fresh());
            }
        }

        return response()->json([
            'success' => true,
            'message' => trans('admin::insurance.team_radar.bulk_reassign_success', [
                'count' => count($data['lead_ids']),
            ]),
        ]);
    }

    /**
     * Agent or MA manual escalation request.
     */
    public function escalateLead(Request $request, int $leadId): JsonResponse
    {
        $reason = $request->input('reason', 'Solicitud de apoyo al Agente Maestro');

        $success = $this->escalationService->escalateLead($leadId, $reason, auth()->id());

        return response()->json([
            'success' => $success,
            'message' => trans('admin::insurance.team_radar.escalated_success'),
        ]);
    }

    /**
     * Master Agent resolves escalation.
     */
    public function resolveEscalation(Request $request, int $leadId): JsonResponse
    {
        $lead = $this->leadRepository->find($leadId);

        if ($lead) {
            $this->leadRepository->update([
                'sla_status'        => 'active',
                'escalated_at'      => null,
                'escalation_reason' => null,
            ], $leadId);

            return response()->json([
                'success' => true,
                'message' => trans('admin::insurance.team_radar.escalation_resolved'),
            ]);
        }

        return response()->json(['success' => false], 404);
    }

    /**
     * Toggle the urgent flag on a single lead's latest pending activity.
     */
    public function urgentFlag(Request $request, int $leadId): JsonResponse
    {
        $lead = $this->leadRepository->findOrFail($leadId);

        $activity = $lead->activities()
            ->where('is_done', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $activity) {
            return response()->json(['success' => false, 'message' => 'No pending activity found.'], 404);
        }

        $newPriority = $activity->priority === 'urgent' ? 'normal' : 'urgent';

        $activity->update(['priority' => $newPriority]);

        return response()->json([
            'success'      => true,
            'new_priority' => $newPriority,
            'message'      => trans('admin::insurance.team_radar.urgent_flag_toggled'),
        ]);
    }
}
