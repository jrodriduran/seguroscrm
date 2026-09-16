<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Settings\TypeDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Repositories\TypeRepository;

class TypeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected TypeRepository ) {}

    /**
     * Display a listing of the type.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(TypeDataGrid::class)->process();
        }

        return view('admin::settings.types.index');
    }

    /**
     * Store a newly created type in storage.
     */
    public function store(): JsonResponse
    {
        ->validate(request(), [
            'name' => ['required', 'unique:lead_types,name'],
            'description' => ['nullable', 'string'],
        ]);

        Event::dispatch('settings.type.create.before');

         = ->typeRepository->create(request()->only(['name', 'description']));

        Event::dispatch('settings.type.create.after', );

        return new JsonResponse([
            'data' => ,
            'message' => trans('admin::app.settings.types.index.create-success'),
        ]);
    }

    /**
     * Show the form for editing the specified type.
     */
    public function edit(int ): View|JsonResponse
    {
         = ->typeRepository->findOrFail();

        return new JsonResponse([
            'data' => ,
        ]);
    }

    /**
     * Update the specified type in storage.
     */
    public function update(int ): JsonResponse
    {
        ->validate(request(), [
            'name' => 'required|unique:lead_types,name,'.,
            'description' => 'nullable|string',
        ]);

        Event::dispatch('settings.type.update.before', );

         = ->typeRepository->update(request()->only(['name', 'description']), );

        Event::dispatch('settings.type.update.after', );

        return new JsonResponse([
            'data' => ,
            'message' => trans('admin::app.settings.types.index.update-success'),
        ]);
    }

    /**
     * Remove the specified type from storage.
     */
    public function destroy(int ): JsonResponse
    {
         = ->typeRepository->findOrFail();

        try {
            Event::dispatch('settings.type.delete.before', );

            ->delete();

            Event::dispatch('settings.type.delete.after', );

            return new JsonResponse([
                'message' => trans('admin::app.settings.types.index.delete-success'),
            ], 200);
        } catch (\Exception ) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.types.index.delete-failed'),
            ], 400);
        }
    }
}