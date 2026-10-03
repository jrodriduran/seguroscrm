<?php

namespace Webkul\Communications\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Communications\Models\Delivery;

/**
 * Creates and moves physical deliveries, and tells the right people.
 */
class Deliveries
{
    public function __construct(protected CommunicationSettings $settings) {}

    public function approvalLimit(): float
    {
        return (float) $this->settings->get('deliveries.approval_over', 25);
    }

    /**
     * New delivery with the client's current mailing address.
     */
    public function request(int $personId, string $item, ?string $note, $cost, ?int $assignedTo, array $links = []): Delivery
    {
        $needsApproval = Delivery::needsApproval($item, $cost, $this->approvalLimit());

        $delivery = Delivery::create($links + [
            'agency_id' => auth()->guard('user')->user()?->agency_id,
            'person_id' => $personId,
            'item' => $item,
            'note' => $note,
            'cost' => $cost !== null && $cost !== '' ? (float) $cost : null,
            'address' => $this->address($personId),
            'status' => $needsApproval ? 'requested' : 'approved',
            'requested_by' => auth()->guard('user')->id(),
            'assigned_to' => $assignedTo ?: $this->defaultAssignee(),
            'approved_at' => $needsApproval ? null : now(),
        ]);

        $client = (string) DB::table('persons')->where('id', $personId)->value('name');
        $label = trans('communications::app.deliveries.items.'.$item);

        if ($needsApproval) {
            $this->notify($this->masters(), trans('communications::app.deliveries.notices.approve', ['item' => $label, 'name' => $client, 'cost' => $delivery->cost ?? '0']), $delivery, true);
        } elseif ($delivery->assigned_to && empty($links['follow_up_id'])) {
            $this->notify([$delivery->assigned_to], trans('communications::app.deliveries.notices.todo', ['item' => $label, 'name' => $client]), $delivery);
        }

        return $delivery;
    }

    public function move(Delivery $delivery, string $status, ?string $tracking = null): void
    {
        $changes = ['status' => $status];

        if ($status === 'approved') {
            $changes += ['approved_by' => auth()->guard('user')->id(), 'approved_at' => now()];
        }

        if ($status === 'sent') {
            $changes += ['sent_at' => now(), 'tracking' => $tracking ?: $delivery->tracking];
        }

        if ($status === 'delivered') {
            $changes += ['delivered_at' => now()];
        }

        $delivery->update($changes);

        if ($status === 'approved' && $delivery->assigned_to) {
            $this->notify([$delivery->assigned_to], trans('communications::app.deliveries.notices.todo', [
                'item' => trans('communications::app.deliveries.items.'.$delivery->item),
                'name' => (string) DB::table('persons')->where('id', $delivery->person_id)->value('name'),
            ]), $delivery);
        }

        // The follow-up a sequence created for it closes when it is sent.
        if (in_array($status, ['sent', 'delivered', 'cancelled'], true) && $delivery->follow_up_id) {
            DB::table('teamwork_follow_ups')->where('id', $delivery->follow_up_id)->where('status', 'open')
                ->update(['status' => 'done', 'resolved_at' => now(), 'resolved_by' => auth()->guard('user')->id(), 'last_activity_at' => now()]);
        }
    }

    public function address(int $personId): ?array
    {
        $raw = DB::table('attribute_values')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->where('attributes.entity_type', 'persons')
            ->where('attributes.code', 'address')
            ->where('attribute_values.entity_id', $personId)
            ->value('attribute_values.json_value');

        $address = json_decode((string) $raw, true);

        return $address && array_filter($address) ? $address : null;
    }

    /**
     * Reception by default (a user whose role mentions reception), else the agency owner.
     */
    public function defaultAssignee(): ?int
    {
        if ($id = (int) $this->settings->get('deliveries.assignee')) {
            return $id;
        }

        $reception = DB::table('users')->join('roles', 'roles.id', '=', 'users.role_id')->where('users.status', 1)
            ->where('roles.name', 'like', '%recep%')->orderBy('users.id')->value('users.id');

        return $reception ? (int) $reception : ($this->masters()[0] ?? null);
    }

    public function masters(): array
    {
        return DB::table('users')->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.permission_type', 'all')->where('users.status', 1)->orderBy('users.id')
            ->pluck('users.id')->map(fn ($id) => (int) $id)->all();
    }

    protected function notify(array $userIds, string $title, Delivery $delivery, bool $urgent = false): void
    {
        $notifier = 'Webkul\\Teamwork\\Services\\Notifier';

        if (class_exists($notifier)) {
            app($notifier)->notify($userIds, 'automation', $title, $delivery->note, route('admin.communications.deliveries.index', ['focus' => $delivery->id], false), $urgent);
        }
    }
}
