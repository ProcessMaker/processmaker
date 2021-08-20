<?php

namespace ProcessMaker\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Notifications as NotificationResource;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\TaskOverdueNotification;

class NotificationController extends Controller
{
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
     * @OA\Get(
     *     path="/notifications",
     *     summary="Returns all notifications that the user has access to",
     *     operationId="getNotifications",
     *     tags={"Notifications"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Only return notifications by status (unread, all, etc.)",
     *         required=false,
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of notifications",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Notification"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 @OA\Schema(ref="#/components/schemas/metadata"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        // This creates notifications for overdue tasks
        $this->notifyOverdueTasks();

        $query = Notification::select(
            'id',
            'read_at',
            'created_at',
            'updated_at',
            'data->type as type',
            'data->name as name',
            'data->message as message',
            'data->processName as processName',
            'data->userName as userName',
            'data->request_id as request_id',
            'data->url as url')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', Auth::user()->id);

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = addslashes($filter);
            $subsearch = '%' . $filter . '%';
            $query->where(function ($query) use ($subsearch, $filter) {
                $query->Where('data->name', 'like', $subsearch)
                    ->orWhere('data->userName', 'like', $subsearch)
                    ->orWhere('data->processName', 'like', $subsearch)
                    ->orWhereRaw("case when read_at is null then 'unread' else 'read' end like '$filter%'");
            });
        }

        //restrict all filters and results to the selected status
        $status = $request->input('status', '');
        switch ($status) {
            case 'read':
                $query->whereNotNull('read_at');
                break;
            case 'unread':
                $query->whereNull('read_at');
                break;
        }

        $response =
            $query->orderBy(
                $request->input('order_by', 'created_at'),
                $request->input('order_direction', 'DESC')
            )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return NotificationResource
     * @throws \Throwable
     *
     * @OA\Post(
     *     path="/notifications",
     *     summary="Save a new notifications",
     *     operationId="createNotification",
     *     tags={"Notifications"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/NotificationEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/Notification")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(Notification::rules());
        $notification = new Notification();
        $notification->fill($request->input());
        $notification->saveOrFail();
        return new NotificationResource($notification);
    }


    /**
     * Display the specified resource.
     *
     * @param Notification $notification
     *
     * @return \Illuminate\Http\Response
     *
     * @internal param id $id
     *
     * @OA\Get(
     *     path="/notifications/{notification_id}",
     *     summary="Get single notification by ID",
     *     operationId="getNotificationById",
     *     tags={"Notifications"},
     *     @OA\Parameter(
     *         description="ID of notification to return",
     *         in="path",
     *         name="notification_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the notification",
     *         @OA\JsonContent(ref="#/components/schemas/Notification")
     *     ),
     * )
     */
    public function show(Notification $notification)
    {
        return new NotificationResource($notification);
    }


    /**
     * Update a user
     *
     * @param Notification $notification
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Throwable
     *
     * @OA\Put(
     *     path="/notifications/{notification_id}",
     *     summary="Update a notification",
     *     operationId="updateNotification",
     *     tags={"Notifications"},
     *     @OA\Parameter(
     *         description="ID of notification to return",
     *         in="path",
     *         name="notification_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/NotificationEditable")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success"
     *     ),
     * )
     */
    public function update(Notification $notification, Request $request)
    {
        $request->validate(Notification::rules($notification));
        $notification->fill($request->input());
        $notification->saveOrFail();
        return response([], 204);
    }


    /**
     * Delete a notification
     *
     * @param Notification $notification
     *
     * @return ResponseFactory|Response
     *
     * @OA\Delete(
     *     path="/notifications/{notification_id}",
     *     summary="Delete a notification",
     *     operationId="deleteNotification",
     *     tags={"Notifications"},
     *     @OA\Parameter(
     *         description="ID of notification to return",
     *         in="path",
     *         name="notification_id",
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
    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response([], 204);
    }


    /**
     * Update notification as read
     *
     * @param Request $request
     *
     * @return Response
     *
     * @OA\Put(
     *     path="/read_notifications",
     *     summary="Mark notifications as read by the user",
     *     operationId="markNotificationAsRead",
     *     tags={"Notifications"},
     *
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          @OA\Property(
     *              property="message_ids",
     *              type="array",
     *              description="list of message ids that will be marked as read",
     *              @OA\Items (type="string")),
     *          @OA\Property(
     *              property="routes",
     *              type="array",
     *              description="all messages that has an url that is in this list will be marked as read",
     *              @OA\Items (type="string"))
     *       ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *     ),
     * )
     */
    public function updateAsRead(Request $request)
    {
        $messageIds = $request->input('message_ids');
        $routes = $request->input('routes');

        Notification::query()
            ->whereIn('id', $messageIds)
            ->orWhereIn('data->url', $routes)
            ->update(['read_at' => Carbon::now()]);
        return response([], 201);
    }

     /**
     * Update notifications as unread
     *
     * @param Request $request
     *
     * @return Response
     *
     * @OA\Put(
     *     path="/unread_notifications",
     *     summary="Mark notifications as unread by the user",
     *     operationId="markNotificationAsUnread",
     *     tags={"Notifications"},
     *
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          @OA\Property(
     *              property="message_ids",
     *              type="array",
     *              description="list of message ids that will be marked as read",
     *              @OA\Items (type="string")),
     *          @OA\Property(
     *              property="routes",
     *              type="array",
     *              description="all messages that has an url that is in this list will be marked as read",
     *              @OA\Items (type="string"))
     *       ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *     ),
     * )
     */

    public function updateAsUnread(Request $request)
    {
        $messageIds = $request->input('message_ids');
        $routes = $request->input('routes');

        $updated = Notification::query()
            ->whereIn('id', $messageIds)
            ->orWhereIn('data->url', $routes)
            ->get();

        Notification::query()
            ->whereIn('id', $messageIds)
            ->orWhereIn('data->url', $routes)
            ->update(['read_at' => null]);
        return response($updated, 201);
    }


    /**
     * Update all notification as read.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     *
     * @OA\Put(
     *     path="/read_all_notifications",
     *     summary="Mark notifications as read by id and type",
     *     operationId="markAllAsRead",
     *     tags={"Notifications"},
     *
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          @OA\Property(
     *              property="id",
     *              type="integer",
     *              description="Polymorphic relation id",
     *              ),
     *          @OA\Property(
     *              property="type",
     *              type="string",
     *              description="Polymorphic relation type",
     *              )
     *       ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *     ),
     * )
     */
    public function updateAsReadAll(Request $request)
    {
        $id = $request->input('id');
        $type = $request->input('type');

        Notification::query()
            ->where('notifiable_id', $id)
            ->where('notifiable_type', $type)
            ->update(['read_at' => Carbon::now()]);
        return response([], 201);
    }

    /**
     * This method find task in overdue status that were not notified to the
     * owner user.
     */
    private function notifyOverdueTasks()
    {
        $inOverdue = ProcessRequestToken::where('user_id', Auth::user()->id)
            ->where('status', 'ACTIVE')
            ->where('due_at', '<', Carbon::now())
            ->where('due_notified', 0)
            ->get();
        foreach($inOverdue as $token) {
            $notifiables = $token->getNotifiables('due');
            NotificationFacade::send($notifiables, new TaskOverdueNotification($token));
        }
    }
}
