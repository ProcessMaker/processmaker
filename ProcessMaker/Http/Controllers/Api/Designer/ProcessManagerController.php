<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use ProcessMaker\Facades\ProcessManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Transformers\ProcessMakerSerializer;
use ProcessMaker\Transformers\ProcessTransformer;

/**
 * Implements endpoints to manage the processes.
 *
 */
class ProcessManagerController extends Controller
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
        $filter = $request->input("filter");
        $start = $request->input("start");
        $limit = $request->input("limit");
        $response = ProcessManager::index($filter, $start, $limit);
        $fractal = new Manager();
        $serializer = new ProcessMakerSerializer();
        $fractal->setSerializer($serializer);
        $fractal->parseExcludes('diagram');
        $json = $fractal->createData(new Collection($response, new ProcessTransformer([
            'prj_uid',
            'prj_name',
            'prj_description',
            'prj_category',
            'prj_type',
            'prj_create_date',
            'prj_update_date',
            'prj_status',
        ])))->toJson();
        return response($json, 200);
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
     * Show the properties of a process.
     *
     * @param \ProcessMaker\Model\Process $process
     *
     * @return array
     */
    public function show(Process $process)
    {
        $fractal = new Manager();
        $serializer = new ProcessMakerSerializer();
        $fractal->setSerializer($serializer);
        $json = $fractal->createData(new Item($process, new ProcessTransformer([
            "prj_uid",
            "prj_name",
            "prj_description",
            "prj_target_namespace",
            "prj_expresion_language",
            "prj_type_language",
            "prj_exporter",
            "prj_exporter_version",
            "prj_create_date",
            "prj_update_date",
            "prj_author",
            "prj_author_version",
            "prj_original_source",
        ])))->toJson();
        return response($json, 200);
    }
}
