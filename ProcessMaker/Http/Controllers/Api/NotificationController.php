<?php

namespace ProcessMaker\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\Notification;
use ProcessMaker\Http\Resources\Notifications as NotificationResource;
use ProcessMaker\Models\User;

class NotificationController extends Controller
{
    public $skipPermissionCheckFor = ['index', 'show'];

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
     *                 @OA\Items(ref="#/components/schemas/notifications"),
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
        $query = Notification::select(
            'id',
            'read_at',
            'created_at',
            'updated_at',
            'data->>type as type',
            'data->>name as name',
            'data->>message as message',
            'data->>processName as processName',
            'data->>userName as userName',
            'data->>request_id as request_id',
            'data->>url as url')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', Auth::user()->id);

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $subsearch = '%' . $filter . '%';
            $query->where(function ($query) use ($subsearch, $filter) {
                $query->Where('data->name', 'like', $subsearch)
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
     *       @OA\JsonContent(ref="#/components/schemas/notificationsEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/notifications")
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
     *     path="/notifications/notificationId",
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
     *         @OA\JsonContent(ref="#/components/schemas/notifications")
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
     *     path="/notifications/notificationId",
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
     *       @OA\JsonContent(ref="#/components/schemas/notificationsEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/notifications")
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
     *     path="/notifications/notificationId",
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
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/notifications")
     *     ),
     * )
     */
    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response([], 204);
    }


    /**
     * Update notifications
     *
     * @param Request $request
     *
     * @return Response
     *
     *     @OA\Put(
     *     path="/read_notifications",
     *     summary="Mark notifications as read by the user",
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
     *         response=204,
     *         description="success",
     *     ),
     * )
     */
    public function updateAsRead(Request $request)
    {
        $messageIds = $request->input('message_ids');
        $routes = $request->input('routes');

        DB::table('notifications')
            ->whereIn('id', $messageIds)
            ->orWhereIn('data->url', $routes)
            ->update(['read_at' => Carbon::now()]);
        return response([], 201);
    }

    public function updateAsUnread(Request $request)
    {
        $messageIds = $request->input('message_ids');
        $routes = $request->input('routes');

        $updated = DB::table('notifications')
            ->whereIn('id', $messageIds)
            ->orWhereIn('data->url', $routes)
            ->get();

        DB::table('notifications')
            ->whereIn('id', $messageIds)
            ->orWhereIn('data->url', $routes)
            ->update(['read_at' => null]);
        return response($updated, 201);
    }
}
