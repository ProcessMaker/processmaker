<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\FormManager;
use ProcessMaker\Model\Form;
use ProcessMaker\Model\Process;
use ProcessMaker\Transformers\FormTransformer;

class FormController
{
    /**
     * Get a list of Forms in a project.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process, Request $request)
    {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('sort_by', 'title'),
            'sort_order' => $request->input('sort_order', 'ASC'),
        ];
        $response = FormManager::index($process, $options);
        return fractal($response, new FormTransformer())->respond();
    }

    /**
     * Get a single Form in a project.
     *
     * @param Process $process
     * @param Form $form
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, Form $form)
    {
        $this->belongsToProcess($process, $form);
        return fractal($form, new FormTransformer())->respond();
    }

    /**
     * Create a new Form in a project.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Request $request)
    {
        $data = [
            'title' => $request->input('title', ''),
            'description' => $request->input('description', '')
        ];
        $data = array_merge($data, $this->formatData($request, ['content']));

        if ($request->has('copy_import')) {
            $data['copy_import'] = $request->input('copy_import');
            return fractal(FormManager::copyImport($process, $data), new FormTransformer())->respond(201);
        }
        $response = FormManager::save($process, $data);
        return fractal($response, new FormTransformer())->respond(201);
    }

    /**
     * Update a Form in a project.
     *
     * @param Process $process
     * @param Form $form
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, Form $form, Request $request)
    {
        $this->belongsToProcess($process, $form);
        $data = $this->formatData($request, ['title', 'description', 'content']);

        if ($data) {
            FormManager::update($process, $form, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a Form in a project.
     *
     * @param Process $process
     * @param Form $form
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, Form $form)
    {
        $this->belongsToProcess($process, $form);
        FormManager::remove($form);
        return response([], 204);
    }

    /**
     * Validate if Form belong to process.
     *
     * @param Process $process
     * @param Form $form
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, Form $form): void
    {
        if ($process->id !== $form->process_id) {
            Throw new DoesNotBelongToProcessException(__('The Form does not belong to this process.'));
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
                $data[$field] = $request->input($field);
            }
        }
        return $data;
    }

}
