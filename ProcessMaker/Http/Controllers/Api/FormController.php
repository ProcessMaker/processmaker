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
     *
     *     @OA\Get(
     *     path="/forms",
     *     summary="Returns all forms that the user has access to",
     *     operationId="getForms",
     *     tags={"Forms"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of forms",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/forms"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
     *             ),
     *         ),
     *     ),
     * )
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
            )->paginate($request->input('per_page', 10));
        return new ApiCollection($response);
    }

    /**
     * Get a single Form.
     *
     * @param Form $form
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Get(
     *     path="/forms/{formsUuid}",
     *     summary="Get single forms by ID",
     *     operationId="getFormsByUuid",
     *     tags={"Forms"},
     *     @OA\Parameter(
     *         description="ID of forms to return",
     *         in="path",
     *         name="formsUuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the forms",
     *         @OA\JsonContent(ref="#/components/schemas/forms")
     *     ),
     * )
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
     *
     *     @OA\Post(
     *     path="/forms",
     *     summary="Save a new forms",
     *     operationId="createForms",
     *     tags={"Forms"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/formsEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/forms")
     *     ),
     * )
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
     *
     *     @OA\Put(
     *     path="/forms/{formsUuid}",
     *     summary="Update a form",
     *     operationId="updateForm",
     *     tags={"Forms"},
     *     @OA\Parameter(
     *         description="ID of form to return",
     *         in="path",
     *         name="formsUuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/formsEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/forms")
     *     ),
     * )
     */
    public function update(Form $form, Request $request)
    {
        $request->validate(Form::rules($form));
        $form->fill($request->input());
        $form->saveOrFail();

        return response([], 204);
    }

    /**
     * Delete a Form.
     *
     * @param Form $form
     *
     * @return ResponseFactory|Response
     *     @OA\Delete(
     *     path="/forms/{formsUuid}",
     *     summary="Delete a form",
     *     operationId="deleteForm",
     *     tags={"Forms"},
     *     @OA\Parameter(
     *         description="ID of form to return",
     *         in="path",
     *         name="formsUuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/forms")
     *     ),
     * )
     */
    public function destroy(Form $form)
    {
        $form->delete();
        return response([], 204);
    }

}
