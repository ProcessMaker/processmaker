<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ProcessRequests;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Http\Resources\ProcessRequests as ProcessRequestResource;

class ProcessRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * /**
     * @OA\Get(
     *     path="/requests",
     *     summary="Returns all process Requests that the user has access to",
     *     operationId="getProcessesRequests",
     *     tags={"ProcessRequests"},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Only return requests by type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"all", "in_progress", "completed"}),
     *     ),
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
    public function index(Request $request)
    {
        $query = ProcessRequest::query();

        $includes = $request->input('include', '');
        foreach (array_filter(explode(',', $includes)) as $include) {
            if (in_array($include, ProcessRequest::$allowedIncludes)) {
                $query->with($include);
            }
        }

        // type filter
        switch ($request->input('type')) {
            case 'started_me':
                $query->startedMe(Auth::user()->id);
                break;
            case 'in_progress':
                if (!Auth::user()->is_administrator) {
                    $query->startedMe(Auth::user()->id);
                }
                $query->inProgress();
                break;
            case 'completed':
                if (!Auth::user()->is_administrator) {
                        $query->startedMe(Auth::user()->id);
                    }
                $query->completed();
                break;
        }

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('name', 'like', $filter)
                    ->orWhere('status', 'like', $filter);
            });
        }

        $response = $query
            ->orderBy(
                $request->input('order_by', 'name'),
                $request->input('order_direction', 'ASC')
            )
            ->paginate($request->input('per_page', 10));

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
     *     path="/requests/process_request_id",
     *     summary="Get single process request by ID",
     *     operationId="getProcessRequestById",
     *     tags={"ProcessRequests"},
     *     @OA\Parameter(
     *         description="ID of process request to return",
     *         in="path",
     *         name="process_id",
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
     * @OA\Put(
     *     path="/requests/process_request_id",
     *     summary="Update a process request",
     *     operationId="updateProcessRequest",
     *     tags={"ProcessRequests"},
     *     @OA\Parameter(
     *         description="ID of process request to return",
     *         in="path",
     *         name="process_id",
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
        if ($httpRequest->status === 'CANCELED') {
            $permission = 'requests.cancel';
            if (!Auth::user()->hasProcessPermission(Process::find($request->process_id), $permission)) {
                throw new AuthorizationException('Not authorized: ' . $permission);
            }
            $this->cancelRequestToken($request);
            return response([], 204);
        }

        $httpRequest->validate(ProcessRequest::rules($request));
        $request->fill($httpRequest->json()->all());
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
     * @OA\Delete(
     *     path="/requests/process_request_id",
     *     summary="Delete a process request",
     *     operationId="deleteProcessRequest",
     *     tags={"ProcessRequests"},
     *     @OA\Parameter(
     *         description="ID of process request to return",
     *         in="path",
     *         name="process_id",
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

    /**
     * Cancel all tokens of request.
     *
     * @param ProcessRequest $request
     * @throws \Throwable
     */
    private function cancelRequestToken(ProcessRequest $request)
    {
        //cancel request
        $request->status = 'CANCELED';
        $request->saveOrFail();

        //Closed tokens
        $request->tokens()->update(['status' => 'CLOSED']);
    }
}
