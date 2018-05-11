<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\DynaformManager;
use ProcessMaker\Model\Dynaform;
use ProcessMaker\Model\Process;

class DynaformController
{
    /**
     * Get a list of Dynaforms in a project.
     *
     * @param Process $process
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process)
    {
        $response = DynaformManager::index($process);
        return response($response, 200);
    }

    /**
     * Get a single Dynaform in a project.
     *
     * @param Process $process
     * @param Dynaform $dynaform
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, Dynaform $dynaform)
    {
        $this->belongsToProcess($process, $dynaform);
        return response($dynaform->toArray(), 200);
    }

    /**
     * Create a new Dynaform in a project.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Request $request)
    {
        $data = [
            'title' => $request->input('dyn_title', ''),
            'description' => $request->input('dyn_description', '')
        ];
        $data = array_merge($data, $this->formatData($request, ['dyn_content']));

        if ($request->has('copy_import')) {
            $data['COPY_IMPORT'] = $request->input('copy_import');
            return response(DynaformManager::copyImport($process, $data), 201);
        }
        $response = DynaformManager::save($process, $data);
        return response($response, 201);
    }

    /**
     * Update a Dynaform in a project.
     *
     * @param Process $process
     * @param Dynaform $dynaform
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, Dynaform $dynaform, Request $request)
    {
        $this->belongsToProcess($process, $dynaform);
        $data = $this->formatData($request, ['dyn_title', 'dyn_description', 'dyn_content']);

        if ($data) {
            DynaformManager::update($process, $dynaform, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a Dynaform in a project.
     *
     * @param Process $process
     * @param Dynaform $dynaform
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, Dynaform $dynaform)
    {
        $this->belongsToProcess($process, $dynaform);
        DynaformManager::remove($dynaform);
        return response([], 204);
    }

    /**
     * Validate if Dynaform belong to process.
     *
     * @param Process $process
     * @param Dynaform $dynaform
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, Dynaform $dynaform): void
    {
        if ($process->id !== $dynaform->process_id) {
            Throw new DoesNotBelongToProcessException(__('The Dynaform does not belong to this process.'));
        }
    }

    /**
     * Format in capital letters to send information.
     *
     * @param Request $request
     * @param array $fields
     *
     * @return array
     */
    private function formatData(Request $request, array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[substr($field, 4)] = $request->input($field);
            }
        }
        return $data;
    }

}
