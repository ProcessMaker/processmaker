<?php

namespace ProcessMaker\Http\Controllers\Api\Administration;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use ProcessMaker\Facades\ProcessCategoryManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Permission;
use ProcessMaker\Model\ProcessCategory;

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
            'filter' => $request->input('filter'),
            'start' => $request->input('start'),
            'limit' => $request->input('limit'),
            'sort_by' => $request->input('sort_by', 'name'),
            'sort_order' => $request->input('sort_order', 'ASC'),
        ];
        $response = ProcessCategoryManager::index($options);
        return response($this->formatList($response), 200);
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
        return response($this->format($response), 201);
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
        return response($this->format($response), 200);
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
        return response($this->format($processCategory), 200);
    }

    /**
     * Format the process category as a JSON response.
     *
     * @param ProcessCategory $processCategory
     *
     * @return array
     */
    public function format(ProcessCategory $processCategory)
    {
        return [
            "cat_uid" => $processCategory->uid,
            "cat_name" => $processCategory->name,
            "cat_total_processes" => isset($processCategory->processes_count)
                ? $processCategory->processes_count : 0,
        ];
    }

    /**
     * Format the process category index as a JSON response.
     *
     * @param Collection $processCategories
     *
     * @return array
     */
    public function formatList(Collection $processCategories)
    {
        $response = [];
        foreach ($processCategories as $processCategory) {
            $response[] = $this->format($processCategory);
        }
        return $response;
    }
}
