<?php

namespace Webkul\Security\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Security\DataGrids\AccessLogDataGrid;

class AccessLogController extends Controller
{
    /**
     * Sign-in / sign-out audit trail.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(AccessLogDataGrid::class)->process();
        }

        return view('security::access-logs.index');
    }
}
