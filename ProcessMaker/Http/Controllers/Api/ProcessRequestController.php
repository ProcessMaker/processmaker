<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ProcessRequests as ProcessRequestResource;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Notifications\ProcessCanceledNotification;

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
     *     tags={"Process Requests"},
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
                $query->inProgress();
                break;
            case 'completed':
                $query->completed();
                break;
            case 'all':
                $query->getQuery()->wheres = [];
                $query->get();
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
            ->get();
        
        $response = $response->filter(function($processRequest) {
            return Auth::user()->can('view', $processRequest);
        })->values();

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
     *     tags={"Process Requests"},
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
     *     tags={"Process Requests"},
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
            if (! Auth::user()->can('cancel', $request->process)) {
                throw new AuthorizationException('Not authorized to cancel this request.');
            }
            $this->cancelRequestToken($request);
            return response([], 204);
        }
        $fields = $httpRequest->json()->all();
        if (array_keys($fields) === ['data']) {
            // Update data edited
            $data = array_merge($request->data, $fields['data']);
            $request->data = $data;
            $request->saveOrFail();
            // Log the data edition
            $user_id   = Auth::id();
            $user_name = $user_id ? Auth::user()->fullname : 'The System';
            Comment::create([
                'type' => 'LOG',
                'user_id' => $user_id,
                'commentable_type' => ProcessRequest::class,
                'commentable_id' => $request->id,
                'subject' => 'Data edited',
                'body' => $user_name . " " . __('has edited the request data'),
            ]);
        } else {
            $httpRequest->validate(ProcessRequest::rules($request));
            $request->fill($fields);
            $request->saveOrFail();
        }
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
     *     tags={"Process Requests"},
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
        //notify to the user that started the request, its cancellation
        $request->user->notify(new ProcessCanceledNotification($request));

        //cancel request
        $request->status = 'CANCELED';
        $request->saveOrFail();

        //Closed tokens
        $request->tokens()->update(['status' => 'CLOSED']);
    }
}
