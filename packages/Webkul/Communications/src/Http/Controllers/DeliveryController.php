<?php

namespace Webkul\Communications\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Models\Delivery;
use Webkul\Communications\Services\CommunicationSettings;
use Webkul\Communications\Services\Deliveries;

/**
 * Communications › Deliveries: the board where reception follows cards,
 * gift cards and kits from request to delivery.
 */
class DeliveryController extends Controller
{
    /**
     * Allowed moves from each status.
     */
    protected const FLOW = [
        'requested' => ['approved', 'cancelled'],
        'approved' => ['purchased', 'sent', 'cancelled'],
        'purchased' => ['sent', 'cancelled'],
        'sent' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function __construct(protected Deliveries $deliveries) {}

    public function index(Request $request, CommunicationSettings $settings): View
    {
        $month = $request->query('month') ?: now()->format('Y-m');
        [$year, $monthNumber] = array_map('intval', explode('-', $month.'-01'));

        $open = Delivery::with(['assignee:id,name', 'requester:id,name'])
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderBy('created_at')
            ->get();

        $closed = Delivery::with('assignee:id,name')
            ->whereIn('status', ['delivered', 'cancelled'])
            ->whereYear('updated_at', $year)->whereMonth('updated_at', $monthNumber)
            ->latest('updated_at')
            ->limit(100)
            ->get();

        $names = DB::table('persons')->whereIn('id', $open->pluck('person_id')->merge($closed->pluck('person_id'))->unique())->pluck('name', 'id');

        $showMoney = bouncer()->hasPermission('financials');

        $spend = $showMoney ? Delivery::whereNotIn('communication_deliveries.status', ['cancelled', 'requested'])
            ->whereYear('communication_deliveries.created_at', $year)->whereMonth('communication_deliveries.created_at', $monthNumber)
            ->leftJoin('users', 'users.id', '=', 'communication_deliveries.requested_by')
            ->selectRaw('coalesce(users.name, ?) as agent, count(*) as total, sum(coalesce(communication_deliveries.cost, 0)) as amount', [trans('communications::app.deliveries.sequences')])
            ->groupBy('agent')
            ->get() : collect();

        return view('communications::deliveries.index', [
            'columns' => collect(['requested', 'approved', 'purchased', 'sent'])->mapWithKeys(fn ($status) => [$status => $open->where('status', $status)->values()]),
            'closed' => $closed,
            'names' => $names,
            'flow' => self::FLOW,
            'month' => $month,
            'showMoney' => $showMoney,
            'spend' => $spend,
            'isOwner' => $this->isOwner(),
            'focus' => (int) $request->query('focus'),
            'limit' => $this->deliveries->approvalLimit(),
            'assignee' => $settings->get('deliveries.assignee'),
            'users' => DB::table('users')->where('status', 1)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $personId = $request->integer('person');

        return view('communications::deliveries.create', [
            'person' => $personId ? DB::table('persons')->where('id', $personId)->first(['id', 'name']) : null,
            'address' => $personId ? $this->deliveries->address($personId) : null,
            'users' => DB::table('users')->where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'limit' => $this->deliveries->approvalLimit(),
            'defaultAssignee' => $this->deliveries->defaultAssignee(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'person_id' => ['required', 'integer', 'exists:persons,id'],
            'item' => ['required', Rule::in(Delivery::ITEMS)],
            'note' => ['nullable', 'string', 'max:500'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $delivery = $this->deliveries->request((int) $data['person_id'], $data['item'], $data['note'] ?? null, $data['cost'] ?? null, $data['assigned_to'] ?? null);

        session()->flash('success', trans('communications::app.deliveries.'.($delivery->status === 'requested' ? 'requested-approval' : 'requested')));

        return redirect()->route('admin.communications.deliveries.index', ['focus' => $delivery->id]);
    }

    public function move(Request $request, int $id): RedirectResponse
    {
        $delivery = Delivery::findOrFail($id);

        $data = $request->validate([
            'status' => ['required', Rule::in(self::FLOW[$delivery->status] ?? [])],
            'tracking' => ['nullable', 'string', 'max:120'],
        ]);

        // Spending money is the agency owner's call.
        abort_if($data['status'] === 'approved' && ! $this->isOwner(), 403);

        $this->deliveries->move($delivery, $data['status'], $data['tracking'] ?? null);

        return redirect()->route('admin.communications.deliveries.index', ['focus' => $delivery->id]);
    }

    public function settings(Request $request, CommunicationSettings $settings): RedirectResponse
    {
        abort_unless($this->isOwner(), 403);

        $data = $request->validate([
            'approval_over' => ['required', 'numeric', 'min:0', 'max:100000'],
            'assignee' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $settings->set('deliveries.approval_over', $data['approval_over']);
        $settings->set('deliveries.assignee', $data['assignee'] ?? null);

        session()->flash('success', trans('communications::app.deliveries.settings-saved'));

        return back();
    }

    protected function isOwner(): bool
    {
        $user = auth()->guard('user')->user();

        if ($user?->role?->permission_type === 'all') {
            return true;
        }

        $scope = 'Webkul\\Teamwork\\Services\\TeamScope';

        return $user && class_exists($scope) && app($scope)->isMasterAgent($user);
    }
}
