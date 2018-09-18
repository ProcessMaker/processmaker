<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Form;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\ApiCollection;

class FormController extends Controller
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
        $query = Form::query();

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('type', 'like', $filter)
                    ->orWhere('config', 'like', $filter);
            });
        }

        $response =
            $query->orderBy(
                $request->input('order_by', 'title'),
                $request->input('order_direction', 'ASC')
            )
            ->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
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
        return new ApiResource($form);
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
        $form = new Form();
        $form->fill($request->input());
        $form->saveOrFail();
        return new ApiResource($form);
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
        $form->fill($request->input());
        $form->saveOrFail();

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
        $form->delete();
        return response([], 204);
    }

}
