<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Notification;
use ProcessMaker\Events\RequestAction;
use ProcessMaker\Exception\PmqlMethodException;
use ProcessMaker\Exception\ReferentialIntegrityException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Filters\Filter;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\ProcessRequests as ProcessRequestResource;
use ProcessMaker\Jobs\CancelRequest;
use ProcessMaker\Jobs\TerminateRequest;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Notifications\ProcessCanceledNotification;
use ProcessMaker\Query\SyntaxError;
use ProcessMaker\RetryProcessRequest;
use ProcessMaker\Traits\ProcessMapTrait;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

class ProcessRequestController extends Controller
{
    use ProcessMapTrait;

    const DOMAIN_CACHE_TIME = 86400;

    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'data',
        'pmql',
    ];

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param bool $getTotal used by Saved Search package to only return a total count instead of actual results
     * @param User $user used by Saved Search package to return accurate counts
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
     *         description="Only return requests by type (all|in_progress|completed)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"all", "in_progress", "completed", "started_me"}),
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
     *                 @OA\Items(ref="#/components/schemas/processRequest"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 ref="#/components/schemas/metadata",
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request, $getTotal = false, User $user = null)
    {
        // If a specific user is specified, use it; otherwise use the authorized user
        if (!$user) {
            $user = Auth::user();
        }

        // Filter request with user permissions
        $query = ProcessRequest::forUser($user);
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
                $query->notCompleted();
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
            $query->filter($filter);
        }

        if ($advancedFilter = $request->input('advanced_filter', '')) {
            Filter::filter($query, $advancedFilter);
        }

        $query->nonSystem();

        $pmql = $request->input('pmql', '');
        if (!empty($pmql)) {
            try {
                $query->getModel()->useDataStoreTable($query, $request->input('data_store_table', ''), $request->input('data_store_columns', []));
                $query->pmql($pmql);
            } catch (SyntaxError $e) {
                return response(['message' => __('Your PMQL contains invalid syntax.')], 400);
            } catch (PmqlMethodException $e) {
                return response(['message' => $e->getMessage(), 'field' => $e->getField()], 400);
            }
        }
        try {
            if ($getTotal === true) {
                return $query->count();
            } elseif ($request->input('total') == 'true') {
                return ['meta' => ['total' => $query->count()]];
            } else {
                $response = $query->orderBy(
                    str_ireplace('.', '->', $request->input('order_by', 'name')),
                    $request->input('order_direction', 'ASC')
                )
                    ->select('process_requests.*')
                    ->withAggregate('processVersion', 'alternative')
                    ->paginate($request->input('per_page', 10));
                $total = $response->total();
            }
        } catch (QueryException $e) {
            throw $e;
            $rawMessage = $e->getMessage();
            if (preg_match("/Column not found: 1054 (.*) in 'where clause'/", $rawMessage, $matches)) {
                $message = $matches[1];
            } else {
                $message = $rawMessage;
            }

            return response(['message' => $message], 400);
        }

        if (isset($response)) {
            // Map each item through its resource
            $response = $response->map(function ($processRequest) use ($request) {
                return new ProcessRequestResource($processRequest);
            });
        } else {
            $response = collect([]);
        }

        return new ApiCollection($response, $total);
    }

    public function getCount(Request $request, $process)
    {
        $query = ProcessRequest::where('process_id', $process);

        return ['meta' => ['total' => $query->count()]];
    }

    public function getDefaultChart(Request $request, $process)
    {
        $countInProgress = ProcessRequest::where('process_id', $process)->inProgress()->count();
        $countCompleted = ProcessRequest::where('process_id', $process)->completed()->count();

        return [
            'data' => [
                'labels' => [__('Completed'), __('In Progress')],
                'datasets' => [
                    'label' => __('Default chart'),
                    'data' => [$countCompleted, $countInProgress],
                    'backgroundColor' => [
                        'CLOSED' => '#62B2FD', // Color for 'Completed'
                        'ACTIVE' => '#9BDFC4', // Color for 'In Progress'
                    ],
                ],
            ],
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param ProcessRequest $request
     *
     * @return Response
     *
     * @OA\Get(
     *     path="/requests/{process_request_id}",
     *     summary="Get single process request by ID",
     *     operationId="getProcessRequestById",
     *     tags={"Process Requests"},
     *     @OA\Parameter(
     *         description="ID of process request to return",
     *         in="path",
     *         name="process_request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the process",
     *         @OA\JsonContent(ref="#/components/schemas/processRequest")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function show(ProcessRequest $request)
    {
        return new ProcessRequestResource($request);
    }

    /**
     * Retry the service, script, and other tasks for a given request
     *
     * @param  ProcessRequest  $request
     * @param  Request  $httpRequest
     *
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function retry(ProcessRequest $request, Request $httpRequest): JsonResponse
    {
        if (!Auth::user()->is_administrator) {
            throw new AuthorizationException(__('Not authorized to complete this request.'));
        }

        if ($request->status !== 'ERROR') {
            return response()->json([
                'message' => __('Only requests with ERROR status can be retried'),
                'success' => false,
            ], 422);
        }

        $retryRequest = RetryProcessRequest::for($request);

        if (!$retryRequest->hasRetriableTasks() || $retryRequest->hasNonRetriableTasks()) {
            return response()->json([
                'message' => [__('No tasks available to retry')],
                'success' => false,
            ]);
        }

        try {
            $retryRequest->retry();

            return response()->json([
                'message' => $retryRequest::$output,
                'success' => true,
            ]);
        } catch (Throwable $throwable) {
            $message = $throwable->getMessage();

            Log::error("ProcessRequest::{$request->id} Retry Failed", [
                'message' => $throwable->getMessage(),
                'line' => $throwable->getLine(),
                'file' => $throwable->getFile(),
                'code' => $throwable->getCode(),
                'trace' => $throwable->getTrace(),
            ]);
        }

        return response()->json([
            'message' => [$message],
            'success' => false,
        ]);
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
     *     path="/requests/{process_request_id}",
     *     summary="Update a process request",
     *     operationId="updateProcessRequest",
     *     tags={"Process Requests"},
     *     @OA\Parameter(
     *         description="ID of process request to return",
     *         in="path",
     *         name="process_request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/processRequestEditable")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function update(ProcessRequest $request, Request $httpRequest)
    {
        if ($httpRequest->post('status') === 'CANCELED') {
            if (!Auth::user()->can('cancel', $request->processVersion)) {
                throw new AuthorizationException(__('Not authorized to cancel this request.'));
            }
            $this->cancelRequestToken($request);

            return response([], 204);
        }
        if ($httpRequest->post('status') === 'COMPLETED') {
            if (!Auth::user()->is_administrator) {
                throw new AuthorizationException(__('Not authorized to complete this request.'));
            }
            if ($request->status != 'ERROR') {
                throw ValidationException::withMessages([
                    'status' => __('Only requests with status: ERROR can be manually completed'),
                ]);
            }
            $this->completeRequest($request);

            return response([], 204);
        }
        $fields = $httpRequest->json()->all();
        if (array_keys($fields) === ['data'] || array_keys($fields) === ['data', 'task_element_id']) {
            if (!Auth::user()->can('editData', $request)) {
                throw new AuthorizationException(__('Not authorized to edit request data.'));
            }

            $task_name = $this->getTaskName($fields, $request);

            // Update data edited
            $data = array_merge($request->data, $fields['data']);
            $request->data = $data;
            $request->saveOrFail();
            // Log the data edition
            $user_id = Auth::id();
            $user_name = $user_id ? Auth::user()->fullname : 'The System';

            if ($task_name) {
                $text = __('has edited the data for ') . $task_name;
            } else {
                $text = __('has edited the request data');
            }

            Comment::create([
                'type' => 'LOG',
                'user_id' => $user_id,
                'commentable_type' => ProcessRequest::class,
                'commentable_id' => $request->id,
                'subject' => 'Data edited',
                'body' => $user_name . ' ' . $text,
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
     *     path="/requests/{process_request_id}",
     *     summary="Delete a process request",
     *     operationId="deleteProcessRequest",
     *     tags={"Process Requests"},
     *     @OA\Parameter(
     *         description="ID of process request to return",
     *         in="path",
     *         name="process_request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/processRequest")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function destroy(ProcessRequest $request)
    {
        try {
            $request->delete();

            return response([], 204);
        } catch (\Exception $e) {
            abort($e->getCode(), $e->getMessage());
        } catch (ReferentialIntegrityException $e) {
            abort($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Trigger a intermediate catch event
     *
     * @param ProcessRequest $request
     * @param string $event
     * @return void
     *
     * @OA\Post(
     *     path="/requests/{process_request_id}/events/{event_id}",
     *     summary="Update a process request event",
     *     operationId="updateProcessRequestEvent",
     *     tags={"Process Requests"},
     *     @OA\Parameter(
     *         description="ID of process request to return",
     *         in="path",
     *         name="process_request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="ID of process event to return",
     *         in="path",
     *         name="event_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     * )
     */
    public function activateIntermediateEvent(ProcessRequest $request, $event)
    {
        // Get the process definition
        $process = $request->process;
        $catchEvent = $request->getVersionDefinitions()->findElementById($event)->getBpmnElementInstance();
        if (!($catchEvent instanceof CatchEventInterface)) {
            return abort(423, __('Invalid element, not a catch event ' . get_class($catchEvent)));
        }
        // Get token and data
        $token = $request->tokens()->where('element_id', $event)->where('status', 'ACTIVE')->first();
        if (!$token) {
            return abort(404, __('Token not found in catch event :element_id', ['element_id' => $event]));
        }

        // Check IPs whitelist
        $whitelist = $catchEvent->getProperty('whitelist');
        $whitelist = $whitelist === 'undefined' ? '' : $whitelist;
        if ($whitelist) {
            $ip = request()->ip();
            [$ipWhitelist, $domainWhitelist] = $this->parseWhitelist($whitelist);
            $domain = Cache::remember("ip_domain_{$ip}", self::DOMAIN_CACHE_TIME, function () use ($ip) {
                return gethostbyaddr($ip);
            });
            if (!IpUtils::checkIp($ip, $ipWhitelist) && !$this->checkDomain($domain, $domainWhitelist)) {
                return abort(403, __('Not authorized to trigger this event.'));
            }
        }

        // Check allowed users
        $allowedUsers = $catchEvent->getProperty('allowedUsers');
        $allowedUsers = $allowedUsers === 'undefined' ? '' : $allowedUsers;
        $allowedGroups = $catchEvent->getProperty('allowedGroups');
        $allowedGroups = $allowedGroups === 'undefined' ? '' : $allowedGroups;
        if ($allowedUsers || $allowedGroups) {
            $users = [];
            foreach (explode(',', $allowedUsers) as $userId) {
                $userId = trim($userId);
                $userId ? $users[$userId] = $userId : null;
            }
            foreach (explode(',', $allowedGroups) as $groupId) {
                $groupId = trim($groupId);
                $groupId ? $process->getConsolidatedUsers($groupId, $users) : null;
            }
            if (!in_array(Auth::id(), $users)) {
                return abort(403, __('Not authorized to trigger this event.'));
            }
        }

        $data = (array) request()->json()->all();

        // Trigger the catch event
        WorkflowManager::completeCatchEvent($process, $request, $token, $data);

        return response([]);
    }

    /**
     * Parse the whitelist parameter
     *
     * @param string $whitelist
     *
     * @return array
     */
    private function parseWhitelist($whitelist)
    {
        $ipWhitelist = [];
        $domainWhitelist = [];
        if ($whitelist && $whitelist !== 'undefined') {
            foreach (explode(',', $whitelist) as $item) {
                if (filter_var($item, FILTER_VALIDATE_IP)) {
                    $ipWhitelist[] = $item;
                } else {
                    $domainWhitelist[] = $item;
                }
            }
        }

        return [$ipWhitelist, $domainWhitelist];
    }

    /**
     * Check if the domain match in the whitelist
     *
     * @param string $domain
     * @param array $whitelist
     *
     * @return bool
     */
    private function checkDomain($domain, $whitelist)
    {
        foreach ($whitelist as $filter) {
            $filter = '/^' . str_replace('\*', '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?', preg_quote($filter, '/')) . '$/';
            if (preg_match($filter, $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cancel all tokens of request.
     *
     * @param ProcessRequest $request
     * @throws Throwable
     */
    private function cancelRequestToken(ProcessRequest $request)
    {
        CancelRequest::dispatchSync($request);
        // Close process request
        $request->status = 'CANCELED';
        $request->save();

        event(new RequestAction($request, RequestAction::ACTION_CANCELED));
    }

    /**
     * Manually complete a request
     *
     * @param ProcessRequest $request
     * @throws Throwable
     */
    private function completeRequest(ProcessRequest $request)
    {
        $notifiables = $request->getNotifiables('completed');
        Notification::send($notifiables, new ProcessCanceledNotification($request));

        // Terminate request
        TerminateRequest::dispatchSync($request);

        event(new RequestAction($request, RequestAction::ACTION_COMPLETED));

        $user = \Auth::user();
        Comment::create([
            'type' => 'LOG',
            'user_id' => $user->id,
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $request->id,
            'subject' => __('Process Manually Completed'),
            'body' => $user->fullname . ' ' . __('manually completed the request from an error state'),
        ]);
    }

    /**
     * Get task name by token fields and request
     *
     * @param array $fields
     * @param ProcessRequest $request
     *
     * @return string
     */
    private function getTaskName($fields, $request)
    {
        if (!array_key_exists('task_element_id', $fields)) {
            return null;
        }
        $task_element_id = $fields['task_element_id'];
        $token = ProcessRequestToken::where(
            ['element_id' => $task_element_id, 'process_request_id' => $request->id]
        )->firstOrFail();

        return $token->element_name;
    }

    /**
     *      Get Information of the last token for the element query
     *
     *      @Parameter
     *          Request $httpRequest
     *          ProcessRequest $request
     *     @return
     *          object data {
     *              element_id,
     *              element_name,
     *              created_at,
     *              completed_at,
     *              username,
     *              sequenceFlow,
     *              count
     *          }
     */
    public function getRequestToken(Request $httpRequest, ProcessRequest $request)
    {
        $httpRequest->validate([
            'element_id' => 'required|string',
        ]);

        $elementId = null;
        $countFlag = false;
        $maxTokenId = $this->getMaxTokenId($request, $httpRequest->element_id);
        if ($maxTokenId === null) {
            $bpmn = $request->process->versions()
                ->where('id', $request->process_version_id)
                ->firstOrFail()
                ->bpmn;

            // Get the source and target node for the sequence flow.
            $xml = $this->loadAndPrepareXML($bpmn);
            $targetAndSourceRef = $this->getRefNodes($xml, $httpRequest->element_id);

            if ($targetAndSourceRef->isNotEmpty()) {
                $targetRef = $targetAndSourceRef['targetRef'];
                $sourceRef = $targetAndSourceRef['sourceRef'];

                // Get the token counts for the target and source nodes.
                $targetTokensCount = $this->getTokenCount($request, $targetRef);
                $sourceTokensCount = $this->getTokenCount($request, $sourceRef);

                // Get the minimum repeated node ID.
                $elementId = ($sourceTokensCount < $targetTokensCount) ? $sourceRef : $targetRef;
                // Get a Flag to adjust the repeat quantity
                $countFlag = $this->getCountFlag($sourceTokensCount, $targetTokensCount, $sourceRef, $request);
            }

            // Get the maximum node ID.
            $httpRequest->merge(['element_id' => $elementId]);
            $maxTokenId = $this->getMaxTokenId($request, $httpRequest->element_id);
        }

        $token = $request->tokens()
            ->where('id', $maxTokenId)
            ->select('user_id', 'element_id', 'element_name', 'created_at', 'completed_at', 'status')
            ->with([
                'user' => fn ($query) => $query->select('id', 'username', 'firstname', 'lastname'),
            ])
            ->firstOrFail();

        // Flags if the object clicked is a Sequence Flow.
        $token->is_sequence_flow = $elementId ? true : false;

        $translatedStatus = match ($token->status) {
            'CLOSED' => __('Completed'),
            'ACTIVE' => __('In Progress'),
            default => $token->status,
        };
        $token->status_translation = $translatedStatus;
        $token->completed_by = $token->completed_at ? ($token->user['fullname'] ?? '-') : '-';

        // Get the number of times the flow has run.
        $tokensCount = $request->tokens()
            ->where([
                'element_id' => $httpRequest->element_id,
                'process_request_id' => $request->id,
            ])->count();
        $token->count = $countFlag ? $tokensCount - 1 : $tokensCount;
        if ($token->count === 0) {
            throw new ModelNotFoundException();
        }

        return new ApiResource($token);
    }

    /**
     * Retrieve the screens requested for a given process request.
     *
     * @param  Request  $httpRequest
     * @param  ProcessRequest  $request
     *
     * @return ApiCollection
     */
    public function screenRequested(Request $httpRequest, ProcessRequest $request)
    {
        $query = ProcessRequestToken::query();
        $query->select('id', 'element_id', 'process_id', 'process_request_id', 'data')
            ->where('process_request_id', $request->id)
            ->whereNotIn('element_type', ['startEvent', 'end_event', 'scriptTask'])
            ->where('status', 'CLOSED')
            ->orderBy('completed_at');

        $response =
            $query->orderBy(
                $httpRequest->input('order_by', 'id'),
                $httpRequest->input('order_direction', 'asc')
            )->paginate($httpRequest->input('per_page', 10));

        $collection = $response->getCollection()
            ->transform(function ($token): ?object {
                $definition = $token->getDefinition();
                if (array_key_exists('screenRef', $definition)) {
                    $screen = $token->getScreenVersion();
                    if ($screen) {
                        $dataManager = new DataManager();
                        $screen->data = $dataManager->getData($token, true);
                        $screen->screen_id = $screen->id;
                        // Assign the task_id from the token object to the screen object
                        $screen->task_id = $token->id;

                        return $screen;
                    }
                }

                return null;
            })
            ->reject(fn ($item) => $item === null)
            ->values();

        $response->setCollection($collection);

        return new ApiCollection($response);
    }

    /**
     * Adding abe flag
     * @param  int  $id
     *
     * @return bool
     */
    public function enableIsActionbyemail($id)
    {
        $query = ProcessRequestToken::query();
        $affectedRows = $query->where('id', $id)
                          ->where('status', 'ACTIVE')
                          ->update(['is_actionbyemail' => true]);

        return $affectedRows > 0;
    }
}
