<?php

namespace Webkul\Teamwork\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Teamwork\Models\Notification;
use Webkul\Teamwork\Services\Notifier;

class NotificationController extends Controller
{
    public function __construct(protected Notifier $notifier) {}

    /**
     * Full history of the user's notifications.
     */
    public function index(): View
    {
        return view('teamwork::notifications.index', [
            'notifications' => Notification::with('actor:id,name')
                ->where('user_id', auth()->guard('user')->id())
                ->latest('id')
                ->paginate(30),
        ]);
    }

    /**
     * Unread counter for the bell (polled by the header).
     */
    public function count(): JsonResponse
    {
        $userId = auth()->guard('user')->id();

        return response()->json([
            'count' => $this->notifier->unreadCount($userId),
            'urgent' => Notification::where('user_id', $userId)->whereNull('read_at')->where('is_urgent', true)->count(),
        ]);
    }

    /**
     * Mark as read and go where the notification points.
     */
    public function open(int $id): RedirectResponse
    {
        $notification = Notification::where('user_id', auth()->guard('user')->id())->findOrFail($id);

        $notification->read_at ??= now();
        $notification->save();

        return redirect($notification->url ?: route('admin.teamwork.center'));
    }

    public function readAll(): RedirectResponse
    {
        Notification::where('user_id', auth()->guard('user')->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
