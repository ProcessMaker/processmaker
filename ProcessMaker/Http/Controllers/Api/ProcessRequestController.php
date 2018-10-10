<?php
namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ProcessRequests;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Http\Resources\ProcessRequests as ProcessRequestResource;

class ProcessRequestController extends Controller
{
    use ResourceRequestsTrait;

    /**
     * Display a listing of the resource.
     *
     * @param Request $httpRequest
     *
     * @return Response
     * 
     *     /**
     * @OA\Get(
     *     path="/requests",
     *     summary="Returns all process Requests that the user has access to",
     *     operationId="getProcessesRequests",
     *     tags={"ProcessRequests"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="list of processes",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/requests"),
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
    public function index(Request $httpRequest)
    {
        $query = ProcessRequest::query();

        $filter = $httpRequest->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('name', 'like', $filter);
            });
        }

        $response =
            $query->orderBy(
                $httpRequest->input('order_by', 'name'),
                $httpRequest->input('order_direction', 'ASC')
            )
                ->paginate($httpRequest->input('per_page', 10));

        return new ApiCollection($response);
    }

    /**
     * Display the specified resource.
     *
     * @param ProcessRequest $request
     *
     * @return Response
     *
     *      * @OA\Get(
     *     path="/requests/{process_request_uuid}",
     *     summary="Get single process request by ID",
     *     operationId="getProcessRequestByUuid",
     *     tags={"ProcessRequests"},
     *     @OA\Parameter(
     *         description="ID of process request to return",
     *         in="path",
     *         name="process_request_uuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the process",
     *         @OA\JsonContent(ref="#/components/schemas/requests")
     *     ),
     * )
     */
    public function show(ProcessRequest $request)
    {
        return new ProcessRequestResource($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $httpRequest
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * 
     * @OA\Post(
     *     path="/requests",
     *     summary="Save a new process request",
     *     operationId="createProcessRequest",
     *     tags={"ProcessRequests"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/requestsEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/requests")
     *     ),
     * )
     */
    public function store(Request $httpRequest)
    {
        $this->encodeRequestUuids($httpRequest, ['process_uuid', 'process_collaboration_uuid', 'user_uuid']);
        $httpRequest->validate(ProcessRequest::rules());
        $processRequest = new ProcessRequest();
        $processRequest->fill($httpRequest->input());
        $processRequest->saveOrFail();
        return new ProcessRequests($processRequest);
    }

    /**
     * Update a request
     *
     * @param ProcessRequest $request
     * @param Request|ProcessRequest $httpRequest
     *
     * @return ResponseFactory|Response
     * 
     *     @OA\Put(
     *     path="/requests/{process_request_uuid}",
     *     summary="Update a process request",
     *     operationId="updateProcessRequest",
     *     tags={"ProcessRequests"},
     *     @OA\Parameter(
     *         description="ID of process request to return",
     *         in="path",
     *         name="process_request_uuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/requestsEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/requests")
     *     ),
     * )
     */
    public function update(ProcessRequest $request, Request $httpRequest)
    {
        $this->encodeRequestUuids($httpRequest, ['process_uuid', 'process_collaboration_uuid', 'user_uuid']);
        $request->fill($httpRequest->json()->all());
        $this->validateModel($request, ProcessRequest::rules($request));
        $request->saveOrFail();
        return response([], 204);
    }

    /**
     * Delete a request
     *
     * @param ProcessRequest $request
     *
     * @return ResponseFactory|Response
     * 
     *     @OA\Delete(
     *     path="/requests/{process_request_uuid}",
     *     summary="Delete a process request",
     *     operationId="deleteProcessRequest",
     *     tags={"ProcessRequests"},
     *     @OA\Parameter(
     *         description="ID of process request to return",
     *         in="path",
     *         name="process_request_uuid",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/requests")
     *     ),
     * )
     */
    public function destroy(ProcessRequest $request)
    {
        $request->delete();
        return response([], 204);
    }
}
