<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Http\Request;
use ProcessMaker\Facades\ProcessManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Transformers\ProcessTransformer;

/**
 * Implements endpoints to manage the processes.
 *
 */
class ProcessesController extends Controller
{

    /**
     * List of processes.
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
            // We want to search off of name and description
            $filter = '%' . $filter . '%';
            $processes = Process::where('name', 'like', $filter)
                ->orWhere('description', 'like', $filter)
                ->paginate($perPage);
        } else {
            $processes = Process::paginate($perPage);
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
