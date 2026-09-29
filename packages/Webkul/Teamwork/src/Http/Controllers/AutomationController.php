<?php

namespace Webkul\Teamwork\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Models\Pipeline;
use Webkul\Teamwork\Models\Automation;
use Webkul\Teamwork\Models\FollowUp;
use Webkul\Teamwork\Services\TeamScope;

class AutomationController extends Controller
{
    public function index(TeamScope $teamScope): View
    {
        $user = auth()->guard('user')->user();

        return view('teamwork::automations.index', [
            'automations' => $this->scoped()->withCount('runs')->orderByDesc('is_active')->orderBy('id')->get(),
            'pipelines' => Pipeline::with(['stages' => fn ($query) => $query->orderBy('sort_order')])->get(),
            'members' => $teamScope->assignableUsers($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'trigger' => ['required', Rule::in(Automation::TRIGGERS)],
            'stage' => ['nullable', 'string', 'max:40'],
            'days_before' => ['nullable', 'integer', 'min:1', 'max:365'],
            'action' => ['required', Rule::in(Automation::ACTIONS)],
            'assign_to' => ['required', Rule::in(Automation::ASSIGNEES)],
            'assign_user_id' => ['nullable', 'required_if:assign_to,user', 'integer', 'exists:users,id'],
            'priority' => ['required', Rule::in([FollowUp::PRIORITY_NORMAL, FollowUp::PRIORITY_URGENT])],
            'due_in_days' => ['required', 'integer', 'min:0', 'max:365'],
            'note_template' => ['nullable', 'string', 'max:2000'],
        ]);

        $conditions = match ($data['trigger']) {
            // "code:won" for every won stage, or "id:141" for one stage.
            'stage_entered' => str_starts_with((string) $data['stage'], 'id:')
                ? ['stage_id' => (int) substr($data['stage'], 3)]
                : ['stage_code' => substr((string) ($data['stage'] ?: 'code:won'), 5)],
            'renewal_upcoming' => ['days_before' => (int) ($data['days_before'] ?? 60)],
            default => [],
        };

        Automation::create([
            'agency_id' => auth()->guard('user')->user()->agency_id,
            'name' => $data['name'],
            'trigger' => $data['trigger'],
            'conditions' => $conditions,
            'action' => $data['action'],
            'assign_to' => $data['assign_to'],
            'assign_user_id' => $data['assign_to'] === 'user' ? $data['assign_user_id'] : null,
            'priority' => $data['priority'],
            'due_in_days' => $data['due_in_days'],
            'note_template' => $data['note_template'] ?? null,
            'is_active' => true,
        ]);

        session()->flash('success', trans('teamwork::app.automations.saved'));

        return redirect()->route('admin.settings.teamwork.automations.index');
    }

    public function toggle(int $id): RedirectResponse
    {
        $automation = $this->scoped()->findOrFail($id);

        $automation->update(['is_active' => ! $automation->is_active]);

        return redirect()->route('admin.settings.teamwork.automations.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->scoped()->findOrFail($id)->delete();

        session()->flash('success', trans('teamwork::app.automations.deleted'));

        return redirect()->route('admin.settings.teamwork.automations.index');
    }

    protected function scoped()
    {
        $agencyId = auth()->guard('user')->user()->agency_id;

        return Automation::query()->when($agencyId, fn ($query) => $query->where(fn ($q) => $q->whereNull('agency_id')->orWhere('agency_id', $agencyId)));
    }
}
