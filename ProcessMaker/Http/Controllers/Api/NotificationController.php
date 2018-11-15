<?php

namespace ProcessMaker\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Horizon\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\Media;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotificationController extends Controller
{
    public function index()
    {
        return response(\Auth::user()->activeNotifications(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param Media $file
     *
     * @return \Illuminate\Http\Response
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
