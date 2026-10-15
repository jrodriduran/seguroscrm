<?php

namespace Webkul\Insurance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Insurance\Models\HouseholdTransition;
use Webkul\Insurance\Services\HouseholdTransitions;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Repositories\LeadRepository;

class HouseholdTransitionController extends Controller
{
    public function __construct(protected LeadRepository $leads, protected HouseholdTransitions $transitions) {}

    protected function lead(int $id): Lead
    {
        $lead = $this->leads->findOrFail($id);
        $this->preventUnauthorizedAccess($lead->user_id);

        return $lead;
    }

    public function index(int $leadId): JsonResponse
    {
        $lead = $this->lead($leadId);

        return response()->json(['data' => HouseholdTransition::where('lead_id', $lead->id)->latest('id')->get()]);
    }

    public function store(Request $request, int $leadId): JsonResponse
    {
        $lead = $this->lead($leadId);
        $data = $request->validate([
            'member_id' => 'required|integer',
            'reason' => 'required|in:age_limit,own_coverage,household_change,move,death,other',
            'plan_type' => 'required|in:marketplace,employer,other',
            'coverage_end_date' => 'required|date_format:Y-m-d',
            'review_on' => 'required|date_format:Y-m-d|before_or_equal:coverage_end_date',
            'note' => 'required|string|max:2000',
        ]);
        $memberId = $data['member_id'];
        unset($data['member_id']);

        return response()->json(['data' => $this->transitions->plan($lead, $memberId, $data, auth()->guard('user')->id())], 201);
    }

    public function resolve(Request $request, int $leadId, int $id): JsonResponse
    {
        $lead = $this->lead($leadId);
        $data = $request->validate([
            'status' => 'required|in:confirmed,cancelled',
            'confirmation_reference' => 'required|string|max:255',
        ]);

        return response()->json(['data' => $this->transitions->resolve($lead, $id, $data['status'], $data['confirmation_reference'], auth()->guard('user')->id())]);
    }

    public function individual(int $leadId, int $id): JsonResponse
    {
        $lead = $this->lead($leadId);
        abort_unless(bouncer()->hasPermission('leads.create'), 403);

        return response()->json(['data' => $this->transitions->individualCase($lead, $id, auth()->guard('user')->id())]);
    }
}
