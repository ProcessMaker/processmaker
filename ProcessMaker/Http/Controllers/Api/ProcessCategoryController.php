<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Events\CategoryCreated;
use ProcessMaker\Events\CategoryDeleted;
use ProcessMaker\Events\CategoryUpdated;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ProcessCategory as Resource;
use ProcessMaker\Models\ProcessCategory;

class ProcessCategoryController extends Controller
{
    /**
     * Display a listing of the Process Categories.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Get(
     *     path="/process_categories",
     *     summary="Returns all processes categories that the user has access to",
     *     operationId="getProcessCategories",
     *     tags={"Process Categories"},
     *     @OA\Parameter(
     *        name="filter",
     *        in="query",
     *        description="Filter results by string. Searches Name and Status. All fields must match exactly.",
     *        @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
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
     *                 @OA\Schema(ref="#/components/schemas/metadata"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $query = ProcessCategory::nonSystem();
        $include = $request->input('include', '');

        if ($include) {
            $include = explode(',', $include);
            $count = array_search('processesCount', $include);
            if ($count !== false) {
                unset($include[$count]);
                $query->withCount('processes');
            }
            if ($include) {
                $query->with($include);
            }
        }

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
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
     *     path="/process_categories/{process_category_id}",
     *     summary="Get single process category by ID",
     *     operationId="getProcessCategoryById",
     *     tags={"Process Categories"},
     *     @OA\Parameter(
     *         description="ID of process category to return",
     *         in="path",
     *         name="process_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
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
     *     tags={"Process Categories"},
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
        $request->validate(ProcessCategory::rules());
        $category = new ProcessCategory();
        $category->fill($request->json()->all());

        $category->saveOrFail();

        // Call Event to store Category Created in Log
        CategoryCreated::dispatch($request->all());

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
     *     path="/process_categories/{process_category_id}",
     *     summary="Update a process Category",
     *     operationId="updateProcessCategory",
     *     tags={"Process Categories"},
     *     @OA\Parameter(
     *         description="ID of process category to return",
     *         in="path",
     *         name="process_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
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
        $request->validate(ProcessCategory::rules($processCategory));
        $original = $processCategory->getOriginal();
        $processCategory->fill($request->json()->all());
        $processCategory->saveOrFail();

        $changes = $processCategory->getChanges();

        //call Event to store Category Changes in Log
        CategoryUpdated::dispatch($processCategory, $changes, $original);

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
     *     path="/process_categories/{process_category_id}",
     *     summary="Delete a process category",
     *     operationId="deleteProcessCategory",
     *     tags={"Process Categories"},
     *     @OA\Parameter(
     *         description="ID of process category to return",
     *         in="path",
     *         name="process_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
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
        if ($processCategory->processes->count() !== 0) {
            return response(
                [
                    'message' => 'The item should not have associated processes',
                    'errors' => ['processes' => $processCategory->processes->count()],
                ],
                422
            );
        }

        $processCategory->delete();

        //Call Event to store Deleted Category on LOG
        CategoryDeleted::dispatch($processCategory);

        return response('', 204);
    }
}
