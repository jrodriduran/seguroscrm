<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Models\Enrollment;
use Webkul\Communications\Models\Sequence;
use Webkul\Communications\Services\SequenceEngine;

/**
 * Sequences from the client's page: enroll by hand, pause, resume, stop.
 */
class EnrollmentController extends Controller
{
    public function __construct(protected SequenceEngine $engine) {}

    public function store(Request $request, int $personId): RedirectResponse
    {
        abort_unless(DB::table('persons')->where('id', $personId)->exists(), 404);

        $data = $request->validate(['sequence_id' => ['required', 'integer', 'exists:communication_sequences,id']]);

        $leadId = DB::table('leads')
            ->join('lead_pipeline_stages as stages', 'stages.id', '=', 'leads.lead_pipeline_stage_id')
            ->where('leads.person_id', $personId)
            ->whereNotIn('stages.code', ['won', 'lost'])
            ->latest('leads.id')
            ->value('leads.id');

        $enrollment = $this->engine->enroll(Sequence::findOrFail($data['sequence_id']), $personId, $leadId ? (int) $leadId : null, null, null, 'manual', auth()->guard('user')->id());

        session()->flash($enrollment ? 'success' : 'error', trans('communications::app.enrollments.'.($enrollment ? 'enrolled' : 'already')));

        return back();
    }

    public function pause(int $id): RedirectResponse
    {
        $this->engine->pause(Enrollment::findOrFail($id), 'paused_by_user');

        return back();
    }

    public function resume(int $id): RedirectResponse
    {
        $this->engine->resume(Enrollment::findOrFail($id));

        return back();
    }

    public function stop(int $id): RedirectResponse
    {
        $enrollment = Enrollment::findOrFail($id);

        if (in_array($enrollment->status, [Enrollment::ACTIVE, Enrollment::PAUSED], true)) {
            $this->engine->finish($enrollment, Enrollment::EXITED, 'stopped_by_user');
        }

        return back();
    }
}
