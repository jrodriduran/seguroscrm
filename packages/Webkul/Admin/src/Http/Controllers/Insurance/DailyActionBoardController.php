<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Lead\Services\DailyActionBoardService;

class DailyActionBoardController extends Controller
{
    public function __construct(
        protected DailyActionBoardService $actionBoardService
    ) {}

    /**
     * Render the Daily Action Board dashboard view.
     */
    public function index(Request $request): View
    {
        $userId = $request->has('all_agents') && auth()->user()->hasPermission('insurance.view_all_action_board')
            ? null
            : auth()->id();

        $data = $this->actionBoardService->getActionBoardData($userId);

        return view('admin::insurance.action_board.index', compact('data'));
    }

    /**
     * Return JSON data for real-time frontend refresh or API consumption.
     */
    public function data(Request $request): JsonResponse
    {
        $userId = $request->has('all_agents') && auth()->user()->hasPermission('insurance.view_all_action_board')
            ? null
            : auth()->id();

        $data = $this->actionBoardService->getActionBoardData($userId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
