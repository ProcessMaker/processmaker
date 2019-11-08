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
     *         @OA\JsonContent(ref="#/components/schemas/dataSource")
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
     *       @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/dataSource")
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
}
