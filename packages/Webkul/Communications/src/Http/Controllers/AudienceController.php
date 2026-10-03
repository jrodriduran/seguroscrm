<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Models\Audience;
use Webkul\Communications\Services\AudienceQuery;

class AudienceController extends Controller
{
    public function __construct(protected AudienceQuery $query) {}

    public function index(): View
    {
        $userIds = bouncer()->getAuthorizedUserIds();

        return view('communications::audiences.index', [
            'audiences' => Audience::orderBy('name')->get()->map(function (Audience $audience) use ($userIds) {
                $audience->total = $this->query->count($audience->rules ?? [], $userIds);

                return $audience;
            }),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Audience(['rules' => []]));
    }

    public function edit(int $id): View
    {
        return $this->form(Audience::findOrFail($id));
    }

    public function store(Request $request): RedirectResponse
    {
        $audience = Audience::create($this->validated($request) + [
            'agency_id' => auth()->guard('user')->user()->agency_id,
            'created_by' => auth()->guard('user')->id(),
        ]);

        session()->flash('success', trans('communications::app.audiences.saved'));

        return redirect()->route('admin.communications.audiences.edit', $audience->id);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        Audience::findOrFail($id)->update($this->validated($request));

        session()->flash('success', trans('communications::app.audiences.saved'));

        return redirect()->route('admin.communications.audiences.edit', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $audience = Audience::findOrFail($id);

        if (DB::table('communication_campaigns')->where('audience_id', $id)->whereIn('status', ['scheduled', 'sending'])->exists()) {
            session()->flash('error', trans('communications::app.audiences.in-use'));

            return back();
        }

        $audience->delete();

        session()->flash('success', trans('communications::app.audiences.deleted'));

        return redirect()->route('admin.communications.audiences.index');
    }

    /**
     * The list as a CSV file (name, email, phone, preferred channel).
     */
    public function export(int $id): StreamedResponse
    {
        $audience = Audience::findOrFail($id);
        $query = $this->query->query($audience->rules ?? [], bouncer()->getAuthorizedUserIds())->orderBy('persons.name');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'email', 'phone', 'preferred_channel']);

            $query->chunk(500, function ($people) use ($out) {
                foreach ($people as $person) {
                    fputcsv($out, [
                        $person->id,
                        $person->name,
                        collect(json_decode((string) $person->emails, true))->pluck('value')->filter()->first(),
                        collect(json_decode((string) $person->contact_numbers, true))->pluck('value')->filter()->first(),
                        $person->preferred_channel,
                    ]);
                }
            });

            fclose($out);
        }, str($audience->name)->slug().'-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    protected function form(Audience $audience): View
    {
        $rules = old('rules', $audience->rules ?? []);
        $userIds = bouncer()->getAuthorizedUserIds();
        $preview = $this->query->query($rules, $userIds);

        return view('communications::audiences.edit', [
            'audience' => $audience,
            'rules' => $rules,
            'total' => (clone $preview)->count(),
            'sample' => (clone $preview)->orderBy('persons.name')->limit(25)->get(),
            'fields' => AudienceQuery::FIELDS,
            'options' => [
                'gender' => ['Male', 'Female', 'Other'],
                'language' => ['Spanish', 'English', 'Portuguese'],
                'marital_status' => ['Single', 'Married', 'Divorced', 'Widowed'],
            ],
            'tags' => DB::table('tags')->orderBy('name')->pluck('name', 'id'),
            'users' => DB::table('users')->where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'stages' => DB::table('lead_pipeline_stages')->join('lead_pipelines', 'lead_pipelines.id', '=', 'lead_pipeline_stages.lead_pipeline_id')
                ->orderBy('lead_pipelines.id')->orderBy('lead_pipeline_stages.sort_order')
                ->get(['lead_pipeline_stages.id', 'lead_pipeline_stages.name', 'lead_pipelines.name as pipeline'])
                ->mapWithKeys(fn ($stage) => [$stage->id => $stage->pipeline.' › '.$stage->name]),
        ]);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'rules' => ['nullable', 'array', 'max:20'],
            'rules.*.field' => ['nullable', Rule::in(array_keys(AudienceQuery::FIELDS))],
            'rules.*.value' => ['nullable', 'string', 'max:120'],
        ]);

        $data['rules'] = collect($data['rules'] ?? [])
            ->filter(fn ($rule) => ! empty($rule['field']) && isset($rule['value']) && $rule['value'] !== '')
            ->map(fn ($rule) => ['field' => $rule['field'], 'value' => trim($rule['value'])])
            ->values()
            ->all();

        return $data;
    }
}
