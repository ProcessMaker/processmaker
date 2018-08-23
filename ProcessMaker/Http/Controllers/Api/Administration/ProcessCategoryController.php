<?php

namespace ProcessMaker\Http\Controllers\Api\Administration;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use ProcessMaker\Facades\ProcessCategoryManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Permission;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Transformers\ProcessCategoryTransformer;

/**
 * Implements endpoints to manage the process categories.
 *
 */
class ProcessCategoryController extends Controller
{

    /**
     * List of process categories.
     *
     * @param Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $this->authorize('has-permission', Permission::PM_SETUP_PROCESS_CATEGORIES);
        $filter = $request->input("filter");
        $start = $request->input("start");
        $limit = $request->input("limit");
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'order_by' => $request->input('order_by', 'name'),
            'order_direction' => $request->input('order_direction', 'ASC'),
        ];
        $response = ProcessCategoryManager::index($options);
        return fractal($response, new ProcessCategoryTransformer())->respond();
    }

    /**
     * Stores a new process category.
     *
     * @param Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $this->authorize('has-permission', Permission::PM_SETUP_PROCESS_CATEGORIES);
        $data = $request->json()->all();
        $response = ProcessCategoryManager::store($data);
        return fractal($response, new ProcessCategoryTransformer())->respond(201);
    }

    /**
     * Update a process category.
     *
     * @param Request $request
     * @param ProcessCategory $processCategory
     *
     * @return array
     */
    public function update(Request $request, ProcessCategory $processCategory)
    {
        $data = $request->json()->all();
        $response = ProcessCategoryManager::update($processCategory, $data);
        return fractal($response, new ProcessCategoryTransformer())->respond(200);
    }

    /**
     * Remove a process category.
     *
     * @param ProcessCategory $processCategory
     *
     * @return array
     */
    public function destroy(ProcessCategory $processCategory)
    {
        ProcessCategoryManager::remove($processCategory);
        return response('', 204);
    }

    /**
     * Show the properties of a process category.
     *
     * @param ProcessCategory $processCategory
     *
     * @return array
     */
    public function show(ProcessCategory $processCategory)
    {
        return fractal($processCategory, new ProcessCategoryTransformer())
               ->respond();
    }
}
