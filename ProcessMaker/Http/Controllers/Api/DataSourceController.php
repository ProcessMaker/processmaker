<?php

namespace ProcessMaker\Http\Controllers\Api;

use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\DataSource;
use ProcessMaker\Models\ProcessRequest;
use Throwable;

class DataSourceController extends Controller
{
    /**
     * Get the list of records of a dataSource
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/datasources",
     *     summary="Returns all Data Sources that the user has access to",
     *     operationId="getDataSources",
     *     tags={"DataSources"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of Data Sources,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/dataSource"),
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
        $query = DataSource::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->where('name', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('authtype', 'like', $filter);
            });
        }
        $response =
            $query->orderBy(
                $request->input('order_by', 'id'),
                $request->input('order_direction', 'ASC')
            )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    /**
     * Get a single DataSource.
     *
     * @param DataSource $dataSource
     *
     * @return ApiResource
     *
     * @OA\Get(
     *     path="/datasources/datasource_id",
     *     summary="Get single Data Source by ID",
     *     operationId="getDataSourceById",
     *     tags={"DataSources"},
     *     @OA\Parameter(
     *         description="ID of Data Source to return",
     *         in="path",
     *         name="datasource_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the Data Source",
     *         @OA\JsonContent(ref="#/components/schemas/dataSource")
     *     ),
     * )
     */
    public function show(DataSource $dataSource)
    {
        return new ApiResource($dataSource);
    }

    /**
     * Create a new DataSource.
     *
     * @param Request $request
     *
     * @return ApiResource
     * @throws Throwable
     *
     * @OA\Post(
     *     path="/datasources",
     *     summary="Save a new Data Source",
     *     operationId="createDataSource",
     *     tags={"DataSources"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/dataSourceEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/dataSource")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(DataSource::rules());

        $dataSource = new DataSource;
        $dataSource->fill($request->input());
        $dataSource->saveOrFail();
        return new ApiResource($dataSource);
    }

    /**
     * Update a DataSource.
     *
     * @param DataSource $dataSource
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     * @throws Throwable
     *
     * @OA\Put(
     *     path="/datasources/datasource_id",
     *     summary="Update a Data Source",
     *     operationId="updateDataSource",
     *     tags={"DataSources"},
     *     @OA\Parameter(
     *         description="ID of Data Source to return",
     *         in="path",
     *         name="datasource_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/dataSourceEditable")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/dataSource")
     *     ),
     * )
     */
    public function update(DataSource $dataSource, Request $request)
    {
        $request->validate(DataSource::rules($dataSource));
        $dataSource->fill($request->input());
        $dataSource->saveOrFail();

        return response([], 204);
    }

    /**
     * Delete a DataSource.
     *
     * @param DataSource $dataSource
     *
     * @return ResponseFactory|Response
     *
     * @throws Exception
     *
     * @OA\Delete(
     *     path="/datasources/datasource_id",
     *     summary="Delete a Data Source",
     *     operationId="deleteDataSource",
     *     tags={"DataSources"},
     *     @OA\Parameter(
     *         description="ID of Data Source to return",
     *         in="path",
     *         name="datasource_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/dataSource")
     *     ),
     * )
     */
    public function destroy(DataSource $dataSource)
    {
        //TODO Check if there are processes that use it.
        $dataSource->delete();
        return response([], 204);
    }

    /**
     * Send a DataSource request.
     *
     * @param DataSource $datasource
     * @param ProcessRequest $processRequest
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     * @OA\Put(
     *     path="/datasources/datasource_id/send",
     *     summary="Send a Data Source request",
     *     operationId="sendDataSource",
     *     tags={"DataSources"},
     *     @OA\Parameter(
     *         description="ID of Data Source to return",
     *         in="path",
     *         name="datasource_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/dataSourceEditable")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/dataSource")
     *     ),
     * )
     */
    public function request(DataSource $datasource, ProcessRequest $processRequest, Request $request)
    {
        $response = $datasource->request($processRequest->data, $request->json('config', []));

        return response($response, 200);
    }
}
