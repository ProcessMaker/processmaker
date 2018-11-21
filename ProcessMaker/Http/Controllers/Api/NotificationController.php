<?php

namespace ProcessMaker\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\Notification;
use ProcessMaker\Http\Resources\Notifications as NotificationResource;

class NotificationController extends Controller
{
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
        $query = Notification::query();

        $response =
            $query->orderBy(
                $request->input('order_by', 'id'),
                $request->input('order_direction', 'ASC')
            )
                ->paginate($request->input('per_page', 10));

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


    /**
     * Returns a list of notifications not read by the authenticated user
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/user_notifications",
     *     summary="Returns a list of notifications not read by the authenticated user",
     *     tags={"Notifications"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of notifications",
     *         @OA\JsonContent(
     *             type="array",
     *              @OA\Items (
     *                      @OA\Property(
     *                          property="dateTime",
     *                          type="date",
     *                          description="date when the notification has been created"
     *                      ),
     *                      @OA\Property(
     *                          property="id",
     *                          type="string",
     *                          description="message id"
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          description="message text"
     *                      ),
     *                      @OA\Property(
     *                          property="url",
     *                          type="string",
     *                          description="associated url of the message"
     *                      ),
     *              )
     *             ),
     *         ),
     *     )
     */
    public function userNotifications()
    {
        return response(\Auth::user()->activeNotifications(), 200);
    }

}
