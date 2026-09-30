<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Models\CallOutcome;
use Webkul\Communications\Services\ConsentRegistry;

class CallOutcomeController extends Controller
{
    public function index(): View
    {
        return view('communications::settings.call-outcomes', [
            'outcomes' => CallOutcome::forAgency(),
            'defaultCodes' => CallOutcome::whereNull('agency_id')->pluck('code')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $agencyId = $this->agencyId();

        $code = Str::slug($data['name'], '_') ?: 'outcome';

        while (CallOutcome::forAgency($agencyId)->contains('code', $code)) {
            $code .= '_'.Str::lower(Str::random(3));
        }

        CallOutcome::create($data + [
            'agency_id' => $agencyId,
            'code' => Str::limit($code, 40, ''),
            'sort_order' => (int) CallOutcome::forAgency($agencyId)->max('sort_order') + 1,
        ]);

        session()->flash('success', trans('communications::app.outcomes.saved'));

        return redirect()->route('admin.settings.communications.outcomes.index');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->editable($id)->update($this->validated($request));

        session()->flash('success', trans('communications::app.outcomes.saved'));

        return redirect()->route('admin.settings.communications.outcomes.index');
    }

    public function toggle(int $id): RedirectResponse
    {
        $outcome = $this->editable($id);

        $outcome->update(['is_active' => ! $outcome->is_active]);

        return redirect()->route('admin.settings.communications.outcomes.index');
    }

    /**
     * Move one place up or down.
     */
    public function move(Request $request, int $id): RedirectResponse
    {
        $list = CallOutcome::forAgency($this->agencyId())->values();
        $index = $list->search(fn ($outcome) => $outcome->id === $id);
        $swap = $index === false ? false : $index + ($request->input('direction') === 'up' ? -1 : 1);

        if ($index !== false && isset($list[$swap])) {
            $ordered = $list->all();
            [$ordered[$index], $ordered[$swap]] = [$ordered[$swap], $ordered[$index]];

            foreach (array_values($ordered) as $position => $outcome) {
                $this->editable($outcome->id)->update(['sort_order' => $position + 1]);
            }
        }

        return redirect()->route('admin.settings.communications.outcomes.index');
    }

    /**
     * Agency outcomes can be removed; shared defaults can only be paused.
     */
    public function destroy(int $id): RedirectResponse
    {
        $outcome = CallOutcome::findOrFail($id);

        $isDefault = CallOutcome::whereNull('agency_id')->where('code', $outcome->code)->exists();

        if ($isDefault || $outcome->agency_id !== $this->agencyId()) {
            $this->editable($id)->update(['is_active' => false]);
        } else {
            $outcome->delete();
        }

        session()->flash('success', trans('communications::app.outcomes.deleted'));

        return redirect()->route('admin.settings.communications.outcomes.index');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tone' => ['required', Rule::in(CallOutcome::TONES)],
            'revokes' => ['nullable', Rule::in([...ConsentRegistry::CHANNELS, 'all'])],
        ]);
    }

    /**
     * The row this agency may change: its own, or its copy of a shared default.
     */
    protected function editable(int $id): CallOutcome
    {
        $outcome = CallOutcome::findOrFail($id);
        $agencyId = $this->agencyId();

        if ($outcome->agency_id === $agencyId) {
            return $outcome;
        }

        abort_unless($outcome->agency_id === null, 404);

        return CallOutcome::firstOrCreate(
            ['agency_id' => $agencyId, 'code' => $outcome->code],
            $outcome->only(['name', 'tone', 'revokes', 'is_active', 'sort_order'])
        );
    }

    protected function agencyId(): ?int
    {
        return auth()->guard('user')->user()?->agency_id;
    }
}
