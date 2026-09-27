<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\PolicyServiceCase;

class ServiceCaseController extends Controller
{
    /**
     * List all service cases with flexible filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PolicyServiceCase::with([
            'policy',
            'lead.person',
            'person',
            'user',
            'comments.user',
        ]);

        if ($request->has('policy_id')) {
            $query->where('policy_id', $request->input('policy_id'));
        }

        if ($request->has('lead_id')) {
            $query->where('lead_id', $request->input('lead_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->boolean('active_only')) {
            $query->whereNotIn('status', ['resolved', 'closed']);
        }

        $cases = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $cases,
            'summary' => [
                'total' => $cases->count(),
                'open' => $cases->where('status', 'open')->count(),
                'in_progress' => $cases->where('status', 'in_progress')->count(),
                'tax_1095a' => $cases->where('category', 'tax_1095a')->count(),
                'resolved' => $cases->where('status', 'resolved')->count(),
            ],
        ]);
    }

    /**
     * Store a newly created service case.
     */
    public function store(Request $request, ?int $policyId = null): JsonResponse
    {
        $targetPolicyId = $policyId ?: $request->input('policy_id');

        $request->validate([
            'policy_id' => $policyId ? 'nullable|integer' : 'required|integer|exists:insurance_policies,id',
            'category' => 'required|string|max:50',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'due_date' => 'nullable|date',
            'user_id' => 'nullable|integer',
            'is_shared_with_client' => 'nullable|boolean',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $policy = InsurancePolicy::findOrFail($targetPolicyId);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('service_cases/'.$policy->id, 'public');
        }

        $dueDate = $request->input('due_date') ? Carbon::parse($request->input('due_date')) : Carbon::today()->addDays(7);

        $serviceCase = PolicyServiceCase::create([
            'policy_id' => $policy->id,
            'lead_id' => $policy->lead_id,
            'person_id' => $policy->person_id ?: $policy->lead?->person_id,
            'user_id' => $request->input('user_id', auth()->id() ?: $policy->user_id),
            'category' => $request->input('category', 'general'),
            'priority' => $request->input('priority', 'normal'),
            'status' => 'open',
            'subject' => $request->input('subject'),
            'description' => $request->input('description'),
            'due_date' => $dueDate,
            'attachment_path' => $attachmentPath,
            'is_shared_with_client' => $request->boolean('is_shared_with_client', false),
        ]);

        // If an initial comment is provided, store it
        if ($request->filled('initial_note')) {
            $serviceCase->comments()->create([
                'user_id' => auth()->id(),
                'comment' => $request->input('initial_note'),
                'is_customer_visible' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => trans('admin::insurance.service_cases.created_success'),
            'data' => $serviceCase->load(['policy', 'user', 'comments']),
        ], 201);
    }

    /**
     * Show service case details.
     */
    public function show(int $id): JsonResponse
    {
        $case = PolicyServiceCase::with([
            'policy',
            'lead.person',
            'person',
            'user',
            'comments.user',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $case,
        ]);
    }

    /**
     * Update existing service case (status, notes, attachment, resolution).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $serviceCase = PolicyServiceCase::findOrFail($id);

        $request->validate([
            'status' => 'nullable|string|in:open,in_progress,pending_carrier,pending_client,resolved,closed',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'resolution_notes' => 'nullable|string',
            'due_date' => 'nullable|date',
            'user_id' => 'nullable|integer',
            'is_shared_with_client' => 'nullable|boolean',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $data = $request->only([
            'priority',
            'subject',
            'description',
            'resolution_notes',
            'user_id',
        ]);

        if ($request->has('due_date')) {
            $data['due_date'] = $request->input('due_date');
        }

        if ($request->has('is_shared_with_client')) {
            $data['is_shared_with_client'] = $request->boolean('is_shared_with_client');
        }

        if ($request->has('status')) {
            $newStatus = $request->input('status');
            $data['status'] = $newStatus;

            if ($newStatus === 'resolved' && ! $serviceCase->resolved_at) {
                $data['resolved_at'] = now();
            }

            if ($newStatus === 'closed' && ! $serviceCase->closed_at) {
                $data['closed_at'] = now();
            }
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('service_cases/'.$serviceCase->policy_id, 'public');
        }

        $serviceCase->update($data);

        return response()->json([
            'success' => true,
            'message' => trans('admin::insurance.service_cases.updated_success'),
            'data' => $serviceCase->fresh(['policy', 'user', 'comments.user']),
        ]);
    }

    /**
     * Add comment or internal/customer note to the service case.
     */
    public function addComment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'comment' => 'required|string',
            'is_customer_visible' => 'nullable|boolean',
        ]);

        $serviceCase = PolicyServiceCase::findOrFail($id);

        $comment = $serviceCase->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->input('comment'),
            'is_customer_visible' => $request->boolean('is_customer_visible', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => trans('admin::insurance.service_cases.comment_added'),
            'data' => $comment->load('user'),
        ]);
    }

    /**
     * Download attached document (e.g. 1095-A, utility bill, id card proof).
     */
    public function downloadAttachment(int $id)
    {
        $serviceCase = PolicyServiceCase::findOrFail($id);

        if (! $serviceCase->attachment_path || ! Storage::disk('public')->exists($serviceCase->attachment_path)) {
            abort(404, 'El archivo adjunto no existe o fue eliminado.');
        }

        return Storage::disk('public')->download($serviceCase->attachment_path);
    }
}
