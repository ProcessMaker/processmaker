<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Notification;
use ProcessMaker\Query\SyntaxError;
use Illuminate\Database\QueryException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ProcessRequests as ProcessRequestResource;
use ProcessMaker\Jobs\TerminateRequest;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Notifications\ProcessCanceledNotification;
use ProcessMaker\Facades\WorkflowManager;
use Symfony\Component\HttpFoundation\IpUtils;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Jobs\CancelRequest;
use ProcessMaker\PmqlHelper;

class ProcessRequestController extends Controller
{
    const DOMAIN_CACHE_TIME = 86400;

    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'data'
    ];
    
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

        $filterBase = $request->input('filter', '');
        if (!empty($filterBase)) {
            $filter = '%' . $filterBase . '%';
            $query->where(function ($query) use ($filter, $filterBase) {
                $query->whereHas('participants', function ($query) use ($filter) {
                    $query->Where('firstname', 'like', $filter);
                    $query->orWhere('lastname', 'like', $filter);
                })->orWhere('name', 'like', $filter)
                    ->orWhere('id', 'like', $filterBase)
                    ->orWhere('status', 'like', $filter);
            });
        }


        $pmql = $request->input('pmql', '');
        if (!empty($pmql)) {
            try {
                $helper = new PmqlHelper('request');
                $query->pmql($pmql, $helper->aliases());
            } catch (QueryException $e) {
                return response(['message' => __('Your PMQL search could not be completed.')], 400);
            } catch (SyntaxError $e) {
                return response(['message' => __('Your PMQL contains invalid syntax.')], 400);
            }
        }
        
        $response = $query->orderBy(
            $request->input('order_by', 'name'),
            $request->input('order_direction', 'ASC')
        )->get();

        if (isset($response)) {
            $response = $response->filter(function ($processRequest) {
                return Auth::user()->can('view', $processRequest);
            })->values();            
        } else {
            $response = collect([]);
        }

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
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the process",
     *         @OA\JsonContent(ref="#/components/schemas/processRequest")
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
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/processRequestEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/processRequest")
     *     ),
     * )
     */
    public function update(ProcessRequest $request, Request $httpRequest)
    {
        if ($httpRequest->post('status') === 'CANCELED') {
            if (!Auth::user()->can('cancel', $request->process)) {
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
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => __('Only requests with status: ERROR can be manually completed')
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
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/processRequest")
     *     ),
     * )
     */
    public function destroy(ProcessRequest $request)
    {
        $request->delete();
        return response([], 204);
    }

    /**
     * Trigger a intermediate catch event
     *
     * @param ProcessRequest $request
     * @param string $event
     * @return void
     */
    public function activateIntermediateEvent(ProcessRequest $request, $event)
    {
        // Get the process definition
        $process = $request->process;
        $catchEvent = $process->getDefinitions()->findElementById($event)->getBpmnElementInstance();
        if (!($catchEvent instanceof CatchEventInterface)) {
            return abort(423, __('Invalid element, not a catch event ' . get_class($catchEvent)));
        }
        // Get token and data
        $token = $request->tokens()->where('element_id', $event)->where('status', 'ACTIVE')->first();
        if (!$token) {
            return abort(400, __('Not found'));
        }

        // Check IPs whitelist
        $whitelist = $catchEvent->getProperty('whitelist');
        $whitelist = $whitelist === 'undefined' ? '' : $whitelist;
        if ($whitelist) {
            $ip = request()->ip();
            list($ipWhitelist, $domainWhitelist) = $this->parseWhitelist($whitelist);
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

        $data = (array)request()->json()->all();

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
     * @return boolean
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
     * @throws \Throwable
     */
    private function cancelRequestToken(ProcessRequest $request)
    {
        CancelRequest::dispatchNow($request);
        // Close process request
        $request->status = 'CANCELED';
        $request->save();
    }

    /**
     * Manually complete a request
     *
     * @param ProcessRequest $request
     * @throws \Throwable
     */
    private function completeRequest(ProcessRequest $request)
    {
        $notifiables = $request->getNotifiables('completed');
        Notification::send($notifiables, new ProcessCanceledNotification($request));

        // Terminate request
        TerminateRequest::dispatchNow($request);

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
}
