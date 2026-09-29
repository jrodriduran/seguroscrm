<?php

namespace Webkul\Security\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Security\DataGrids\TwoFactorUserDataGrid;
use Webkul\Security\Models\AccessLog;
use Webkul\Security\Repositories\AccessLogRepository;
use Webkul\Security\Services\TwoFactorManager;
use Webkul\User\Models\User;

class TwoFactorUserController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TwoFactorManager $twoFactor,
        protected AccessLogRepository $accessLogRepository,
    ) {}

    /**
     * Team overview: who has two-factor on.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(TwoFactorUserDataGrid::class)->process();
        }

        return view('security::two-factor.users');
    }

    /**
     * Reset a user's two-factor (lost phone). They enrol again at next sign-in if the policy requires it.
     */
    public function reset(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $admin = auth()->guard('user')->user();

        abort_if($admin->agency_id && $user->agency_id !== $admin->agency_id, 404);

        $this->twoFactor->disable($user);

        $this->accessLogRepository->record(AccessLog::EVENT_MFA_RESET, $user->id, $user->email.' ← '.$admin->email);

        return response()->json(['message' => trans('security::app.two-factor.users.reset-success', ['name' => $user->name])]);
    }
}
