<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Laravel\Horizon\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\ProcessRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSource;
use ProcessMaker\Packages\Connectors\DataSources\Jobs\DataSource as DataSourceJob;
use ProcessMaker\Exception\HttpResponseException;


class DataSourcesController extends Controller
{

    /**
     * Get the list of records of a Data Source
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/data_sources",
     *     summary="Returns all Data Sources that the user has access to",
     *     operationId="getDataSources",
     *     tags={"Data Sources"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of Data Sources",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(@OA\JsonContent()),
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
        $query = DataSource::nonSystem()
            ->select('data_sources.*')
            ->leftJoin('data_source_categories as category', 'data_sources.data_source_category_id', '=', 'category.id');

        $include = $request->input('include', '');

        if ($include) {
            $include = explode(',', $include);
            $count = array_search('categoryCount', $include);
            if ($count !== false) {
                unset($include[$count]);
                $query->withCount('category');
            }
            if ($include) {
                $query->with($include);
            }
        }

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->where('data_sources.name', 'like', $filter)
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
     * Get a single Data Source.
     *
     * @param DataSource $dataSource
     *
     * @return ApiResource
     *
     * @OA\Get(
     *     path="/data_sources/data_source_id",
     *     summary="Get single Data Source by ID",
     *     operationId="getDataSourceById",
     *     tags={"Data Sources"},
     *     @OA\Parameter(
     *         description="ID of Data Source to return",
     *         in="path",
     *         name="data_source_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the Data Source",
     *         @OA\JsonContent(
     *          @OA\Property(property="id", type="string", format="id"),
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="description", type="string"),
     *          @OA\Property(property="endpoints", type="string"),
     *          @OA\Property(property="mappings", type="string"),
     *          @OA\Property(property="authtype", type="string"),
     *          @OA\Property(property="credentials", type="string"),
     *          @OA\Property(property="status", type="string"),
     *          @OA\Property(property="data_source_category_id", type="string"),
     *          )
     *     ),
     * )
     */
    public function show(DataSource $dataSource)
    {
        return new ApiResource($dataSource);
    }



    /**
     * Execute a data Source endpoint
     *
     * @param ProcessRequest $request
     * @param DataSource $dataSource
     * @param Request $httpRequest
     *
     * @return Response
     *
     * @OA\Schema(
     *   schema="DataSourceCallParameters",
     *   @OA\Property(property="endpoint", type="string")
     * ),
     *
     * @OA\Post(
     *     path="request/{request_id}/data_source/{data_source_id}",
     *     summary="execute Data Source",
     *     operationId="executeDataSource",
     *     tags={"Data Sources"},
     *     @OA\Parameter(
     *         description="ID of the request in whose context the datasource will be executed",
     *         in="path",
     *         name="request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="ID of DataSource to be run",
     *         in="path",
     *         name="data_source_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          @OA\Property(property="config", ref="#/components/schemas/DataSourceCallParameters")
     *       )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent()
     *     ),
     * )
     */
    public function executeDataSource(ProcessRequest $request, DataSource $dataSource, Request $httpRequest)
    {
        $config= $httpRequest->json()->get('config');
        try {
            $response = $dataSource->request($request->data, $config);
            return response($response, 200);
        } catch (HttpResponseException $exception) {
            return response($exception->body, $exception->status);
        }
    }

    /**
     * Create a new Data Source.
     *
     * @param Request $request
     *
     * @return ApiResource
     *
     * @throws Throwable
     *
     * @OA\Post(
     *     path="/data_sources",
     *     summary="Save a new Data Source",
     *     operationId="createDataSource",
     *     tags={"Data Sources"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent()
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
     * Update a Data Source.
     *
     * @param DataSource $dataSource
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     * @throws Throwable
     *
     * @OA\Put(
     *     path="/data_sources/data_source_id",
     *     summary="Update a Data Source",
     *     operationId="updateDataSource",
     *     tags={"Data Sources"},
     *     @OA\Parameter(
     *         description="ID of Data Source to return",
     *         in="path",
     *         name="data_source_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent()
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
     * Delete a Data Source.
     *
     * @param DataSource $dataSource
     *
     * @return ResponseFactory|Response
     *
     * @throws Exception
     *
     * @OA\Delete(
     *     path="/data_sources/data_source_id",
     *     summary="Delete a Data Source",
     *     operationId="deleteDataSource",
     *     tags={"Data Sources"},
     *     @OA\Parameter(
     *         description="ID of Data Source to return",
     *         in="path",
     *         name="data_source_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent()
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
     * Send a Data Source request.
     *
     * @param DataSource $dataSource
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     * @OA\Post(
     *     path="/data_sources/data_source_id/test",
     *     summary="Send a Data Source request",
     *     operationId="sendDataSource",
     *     tags={"Data Sources"},
     *     @OA\Parameter(
     *         description="ID of Data Source to return",
     *         in="path",
     *         name="data_source_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent()
     *     ),
     * )
     */
    public function test(DataSource $dataSource, Request $request)
    {
        if (!$request->has('data')) {
            return response([], 204);
        }
        $data = $request->get('data') ?: [];
        $data['data'] = $data['testData'] ?? [];
        $data['config'] = $data['testConfig'] ?? [];
        $data['config']['body'] = $data['body'] ?? [];
        $data['config']['endpoint'] = $data['purpose'];
        $credentials = $data['credentials'] ?? [];
        $credentials= is_string($credentials) ? [] : $credentials;
        if ($request->has('immediate') && $request->get('immediate')) {
            $response = DataSourceJob::dispatchNow($dataSource, $request->user(), $data, $credentials, true);
            return response($response['response'], $response['status']);
        }
        dispatch(new DataSourceJob($dataSource, $request->user(), $data, $credentials));
        return response([], 204);
    }
}
