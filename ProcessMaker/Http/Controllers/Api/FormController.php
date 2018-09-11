<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Facades\FormManager;
use ProcessMaker\Models\Form;
use ProcessMaker\Transformers\FormTransformer;

class FormController
{
    /**
     * Get a list of Forms.
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function index(Request $request)
    {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('order_by', 'title'),
            'sort_order' => $request->input('order_direction', 'ASC'),
        ];
        $response = FormManager::index($options);
        return fractal($response, new FormTransformer())->respond();
    }

    /**
     * Get a single Form.
     *
     * @param Form $form
     *
     * @return ResponseFactory|Response
     */
    public function show(Form $form)
    {
        return fractal($form, new FormTransformer())->respond();
    }

    /**
     * Create a new Form.
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Request $request)
    {
        $request->validate(Form::rules());
        $data = [
            'title' => $request->input('title', ''),
            'description' => $request->input('description', '')
        ];
        $data = array_merge($data, $this->formatData($request, ['content']));

        $response = FormManager::save($data);
        return fractal($response, new FormTransformer())->respond(201);
    }

    /**
     * Update a Form.
     *
     * @param Form $form
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function update(Form $form, Request $request)
    {
        $request->validate(Form::rules($form));
        $data = $this->formatData($request, ['title', 'description', 'content']);

        if ($data) {
            FormManager::update($form, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a Form.
     *
     * @param Form $form
     *
     * @return ResponseFactory|Response
     */
    public function destroy(Form $form)
    {
        FormManager::remove($form);
        return response([], 204);
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
