<?php

namespace ProcessMaker\Http\Controllers\Api;

use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\DatasourceCategory;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use Throwable;

class DatasourceCategoryController extends Controller
{
    /**
     * Display a listing of the Datasource Categories.
     *
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/datasource_categories",
     *     summary="Returns all datasources categories that the user has access to",
     *     operationId="getDatasourceCategories",
     *     tags={"Datasource Categories"},
     *     @OA\Parameter(
     *             parameter="filter",
     *             name="filter",
     *             in="query",
     *             description="Filter results by string. Searches Name, Description, and Status. All fields must match exactly.",
     *             @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of datasources categories",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/DatasourceCategory"),
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
        $query = DatasourceCategory::nonSystem();
        $include = $request->input('include', '');

        if ($include) {
            $include = explode(',', $include);
            $count = array_search('datasourcesCount', $include);
            if ($count !== false) {
                unset($include[$count]);
                $query->withCount('datasources');
            }
            if ($include) {
                $query->with($include);
            }
        }

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
        $response = $query->paginate($request->input('per_page', 10));
        return new ApiCollection($response);
    }

    /**
     * Display the specified datasource category.
     *
     * @param DatasourceCategory $datasourceCategory
     *
     * @return ApiResource
     *     * @OA\Get(
     *     path="/datasource_categories/{datasource_category_id}",
     *     summary="Get single datasource category by ID",
     *     operationId="getDatasourceCategoryById",
     *     tags={"Datasource Categories"},
     *     @OA\Parameter(
     *         description="ID of datasource category to return",
     *         in="path",
     *         name="datasource_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the datasource",
     *         @OA\JsonContent(ref="#/components/schemas/DatasourceCategory")
     *     ),
     * )
     */
    public function show(DatasourceCategory $datasourceCategory)
    {
        return new ApiResource($datasourceCategory);
    }

    /**
     * Store a newly created Datasource Category in storage
     *
     * @param Request $request
     *
     * @return ApiResource
     *
     * @throws Throwable
     *
     *     * @OA\Post(
     *     path="/datasource_categories",
     *     summary="Save a new Datasource Category",
     *     operationId="createDatasourceCategory",
     *     tags={"Datasource Categories"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/DatasourceCategoryEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/DatasourceCategory")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(DatasourceCategory::rules());
        $category = new DatasourceCategory();
        $category->fill($request->json()->all());
        $category->saveOrFail();
        return new ApiResource($category);
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param DatasourceCategory $datasourceCategory
     *
     * @return ApiResource
     *
     * @throws Throwable
     *
     * @OA\Put(
     *     path="/datasource_categories/{datasource_category_id}",
     *     summary="Update a datasource Category",
     *     operationId="updateDatasourceCategory",
     *     tags={"Datasource Categories"},
     *     @OA\Parameter(
     *         description="ID of datasource category to return",
     *         in="path",
     *         name="datasource_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/DatasourceCategoryEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/DatasourceCategory")
     *     ),
     * )
     */
    public function update(Request $request, DatasourceCategory $datasourceCategory)
    {
        $request->validate(DatasourceCategory::rules($datasourceCategory));
        $datasourceCategory->fill($request->json()->all());
        $datasourceCategory->saveOrFail();
        return new ApiResource($datasourceCategory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DatasourceCategory $datasourceCategory
     *
     * @return ResponseFactory|Response
     *
     * @throws Exception
     *
     * @OA\Delete(
     *     path="/datasource_categories/{datasource_category_id}",
     *     summary="Delete a datasource category",
     *     operationId="deleteDatasourceCategory",
     *     tags={"Datasource Categories"},
     *     @OA\Parameter(
     *         description="ID of datasource category to return",
     *         in="path",
     *         name="datasource_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success"
     *     ),
     * )
     */
    public function destroy(DatasourceCategory $datasourceCategory)
    {
        if ($datasourceCategory->datasources->count() !== 0) {
            return response (
                ['message'=>'The item should not have associated datasources',
                    'errors'=> ['datasources' => $datasourceCategory->datasources->count()]],
                    422);
        }

        $datasourceCategory->delete();
        return response('', 204);
    }
}
