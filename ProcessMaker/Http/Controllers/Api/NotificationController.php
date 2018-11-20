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
     * Returns a list of notifications not read by the authenticated user
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/notifications",
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
    public function index()
    {
        return response(\Auth::user()->activeNotifications(), 200);
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
     * Update notifications
     *
     * @param Request $request
     *
     * @return Response
     *
     *     @OA\Put(
     *     path="/notification",
     *     summary="Update notifications",
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
    public function update(Request $request)
    {
        $messageIds = $request->input('message_ids');
        $routes = $request->input('routes');

        DB::table('notifications')
            ->whereIn('id', $messageIds)
            ->orWhereIn('data->url', $routes)
            ->update(['read_at' => Carbon::now()]);
        return response([], 201);
    }
}
