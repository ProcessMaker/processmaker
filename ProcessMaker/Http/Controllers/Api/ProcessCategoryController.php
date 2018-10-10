<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ProcessCategory as Resource;

class ProcessCategoryController extends Controller
{
    use ResourceRequestsTrait;
    /**
     * Display a listing of the Process Categories.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/process_categories",
     *     summary="Returns all processes categories that the user has access to",
     *     operationId="getProcessCategories",
     *     tags={"ProcessCategories"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="list of processes categories",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ProcessCategory"),
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
        $query = ProcessCategory::query();
        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->Where('name', 'like', $filter)
                    ->orWhere('status', 'like', $filter);
            });
        }
        if ($request->has('status')) {
            $query->where('status', 'like', $request->input('status'));
        }
        $query->orderBy(
            $request->input('order_by', 'name'),
            $request->input('order_direction', 'asc')
        );
        $include = $this->getRequestInclude($request);
        $query->with($include);
        $response = $query->paginate($request->input('per_page', 10));
        return new ApiCollection($response);
    }

    /**
     * Display the specified Process category.
     *
     * @param ProcessCategory $processCategory
     *
     * @return \Illuminate\Http\JsonResponse
     *     * @OA\Get(
     *     path="/process_categories/{process_category_uuid}",
     *     summary="Get single process category by ID",
     *     operationId="getProcessCategoryByUuid",
     *     tags={"ProcessCategories"},
     *     @OA\Parameter(
     *         description="ID of process category to return",
     *         in="path",
     *         name="process_category_uuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the process",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessCategory")
     *     ),
     * )
     */
    public function show(ProcessCategory $processCategory)
    {
        return new Resource($processCategory);
    }

    /**
     * Store a newly created Process Category in storage
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     *     * @OA\Post(
     *     path="/process_categories",
     *     summary="Save a new process Category",
     *     operationId="createProcessCategory",
     *     tags={"ProcessCategories"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ProcessCategoryEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessCategory")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $category = new ProcessCategory();
        $category->fill($request->json()->all());
        $this->validateModel($category, ProcessCategory::rules($category));
        $category->saveOrFail();
        return new Resource($category);
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param ProcessCategory $processCategory
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     *      * @OA\Put(
     *     path="/process_categories/{process_category_uuid}",
     *     summary="Update a process Category",
     *     operationId="updateProcessCategory",
     *     tags={"ProcessCategories"},
     *     @OA\Parameter(
     *         description="ID of process category to return",
     *         in="path",
     *         name="process_category_uuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ProcessCategoryEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessCategory")
     *     ),
     * )
     */
    public function update(Request $request, ProcessCategory $processCategory)
    {
        $processCategory->fill($request->json()->all());
        //validate model trait
        $this->validateModel($processCategory, ProcessCategory::rules($processCategory));
        $processCategory->saveOrFail();
        return new Resource($processCategory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ProcessCategory $processCategory
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * 
     *      * @OA\Delete(
     *     path="/process_categories/{process_category_uuid}",
     *     summary="Delete a process category",
     *     operationId="deleteProcessCategory",
     *     tags={"ProcessCategories"},
     *     @OA\Parameter(
     *         description="ID of process category to return",
     *         in="path",
     *         name="process_category_uuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/Process")
     *     ),
     * )
     */
    public function destroy(ProcessCategory $processCategory)
    {
        $this->validateModel($processCategory, [
            'processes' => 'empty'
        ]);

        $processCategory->delete();
        return response('', 204);
    }
}
