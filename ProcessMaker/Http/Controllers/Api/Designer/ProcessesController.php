<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Facades\ProcessManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Transformers\ProcessTransformer;

/**
 * Implements endpoints to manage the processes.
 *
 */
class ProcessesController extends Controller
{

    /**
     * List of processes. What is returned is ordered by category and id (uncategorized shows last)
     *
     * @param Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        // Get parameters for our request with defaults
        $filter = $request->input("filter", null);
        $perPage = $request->input("per_page", 10);

        if($filter) {
            // We want to search off of name and description and category name
            // Cannot join on table because of Eloquent's lack of specific table column names in generated SQL
            // See: https://github.com/laravel/ideas/issues/347
            $filter = '%' . $filter . '%';
            // Find matching categories
            $categories = ProcessCategory::where('name', 'like', $filter)->get();
            $processes = Process::where('name', 'like', $filter)
                ->orWhere('description', 'like', $filter)
                ->orWhereIn('process_category_id', $categories->pluck('id'))
                // We need minus to ensure null categories are sorted AFTER
                ->orderBy(DB::raw('-process_category_id'), 'desc')
                ->orderBy('id')
                ->paginate($perPage);
        } else {
            $processes = Process::orderBy(DB::raw('-process_category_id'), 'desc')
                ->orderBy('id')
                ->paginate($perPage);
        }

        // Now, let's return with fractal to standardize our api output
        return fractal($processes, new ProcessTransformer())->respond(200);
   }

    /**
     * Stores a new process.
     *
     * @param Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $data = $request->json()->all();
        $response = ProcessManager::store($data);
        return response($this->format($response), 201);
    }

    /**
     * Update a process.
     *
     * @param Request $request
     * @param \ProcessMaker\Model\Process $process
     *
     * @return array
     */
    public function update(Request $request, Process $process)
    {
        $data = $request->json()->all();
        $response = ProcessManager::update($process, $data);
        return response($this->format($response), 200);
    }

    /**
     * Remove a process.
     *
     * @param \ProcessMaker\Model\Process $process
     *
     * @return array
     */
    public function remove(Process $process)
    {
        ProcessManager::remove($process);
        return response('', 204);
    }

    /**
     * Retrieve and show a single process
     *
     * @param \ProcessMaker\Model\Process $process
     *
     * @return array
     */
    public function show(Process $process)
    {
        return fractal($process, new ProcessTransformer())->respond(200);
    }
}
