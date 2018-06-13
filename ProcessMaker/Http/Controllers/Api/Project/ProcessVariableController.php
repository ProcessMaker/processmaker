<?php

namespace ProcessMaker\Http\Controllers\Api\Project;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessVariable;
use Ramsey\Uuid\Uuid;

/**
 * Handles process variables API's requests
 */
class ProcessVariableController
{
    /**
     * Gets the list of all variables in a process
     *
     * @param Process $process
     * @return array
     */
    public function index(Process $process)
    {
        return ProcessVariable::where('process_id', $process->id)
            ->get()
            ->toArray();
    }

    /**
     * Returns one variable
     *
     * @param Process $process
     * @param ProcessVariable $variable
     * @return mixed
     */
    public function show(Process $process, ProcessVariable $variable)
    {
        if ($process->id !== $variable->process->id) {
            $variable->throwValidationException();
        }
        return $variable;
    }

    /**
     * Inserts one process variable
     *
     * @param Request $request
     * @param Process $process
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request, Process $process)
    {
        $variable = new ProcessVariable();
        $variable->process_id = $process->id;
        $this->mapRequestToVariable($request, $variable);

        try {
            $variable->saveOrFail();
        } catch (QueryException $ex) {
            return response($ex->getMessage(), 400);
        }

        // we get the saved variable

        return response($variable->refresh(), 201);
    }

    /**
     * Updates a process variable data
     *
     * @param Request $request
     * @param Process $process
     * @param ProcessVariable $variable
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request, Process $process, ProcessVariable $variable)
    {
        if ($process->id !== $variable->process->id) {
            $variable->throwValidationException();
        }

        $this->mapRequestToVariable($request, $variable);

        $variable->saveOrFail();

        return response($variable->refresh(), 200);
    }

    /**
     * Delete a process variable
     *
     * @param Process $process
     * @param ProcessVariable $variable
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function remove(Process $process, ProcessVariable $variable)
    {
        if ($process->id !== $variable->process->id) {
            $variable->throwValidationException();
        }

        $variable->delete();
        return response(null, 204);
    }

    /**
     * Maps the data that comes in the request to a process variable
     *
     * @param Request $request
     * @param ProcessVariable $variable
     */
    private function mapRequestToVariable(Request $request, ProcessVariable $variable)
    {
        $fieldsList = [
            'name',
            'field_type',
            'field_size',
            'label',
            'db_source_id',
            'sql',
            'null',
            'default',
            'accepted_values',
            'input_document_id'
        ];

        $fieldsInRequest = array_keys($request->all());
        foreach ($fieldsList as $field) {
            $fieldInLowerCase = strtolower($field);
            if (in_array($fieldInLowerCase, $fieldsInRequest)) {
                $variable->$field = $request->$fieldInLowerCase;
            }
        }
    }
}
