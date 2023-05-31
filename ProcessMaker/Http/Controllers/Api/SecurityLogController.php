<?php

namespace ProcessMaker\Http\Controllers\Api;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Helpers\SensitiveDataHelper;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\SecurityLogs;
use ProcessMaker\Jobs\DownloadSecurityLog;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\User;

class SecurityLogController extends Controller
{
    /**
     * Get a list of Security Logs.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/security-logs",
     *     summary="Returns all security logs",
     *     operationId="getSecurityLogs",
     *     tags={"Security Logs"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of security logs",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/securityLog"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Schema(ref="#/components/schemas/metadata"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $query = SecurityLog::query();

        if ($filter = $request->input('filter')) {
            $filter = '%' . mb_strtolower($filter) . '%';
            $query->where('event', 'like', $filter)
                  ->orWhere(DB::raw('LOWER(ip)'), 'like', $filter)
                  ->orWhere(DB::raw("LOWER(meta->>'$.browser.name')"), 'like', $filter)
                  ->orWhere(DB::raw("LOWER(meta->>'$.os.name')"), 'like', $filter);
        }

        if ($orderBy = $request->input('order_by')) {
            $orderBy = DB::raw(preg_replace('/\.(.+)/', "->>'\$.$1'", $orderBy, 1));

            $orderDirection = $request->input('order_direction');

            if (!$orderDirection) {
                $orderDirection = 'asc';
            }

            $query->orderBy($orderBy, $orderDirection);
        }

        if ($pmql = $request->input('pmql')) {
            $query->pmql($pmql);
        }

        $response = $query->get();

        return new ApiCollection($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/security-logs/{securityLog}",
     *     summary="Get single security log by ID",
     *     operationId="getSecurityLog",
     *     tags={"Security Logs"},
     *     @OA\Parameter(
     *         description="ID of security log to return",
     *         in="path",
     *         name="securityLog",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the security log",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/securityLog"),
     *             )
     *         ),
     *     ),
     * )
     */
    public function show(SecurityLog $securityLog)
    {
        return new ApiResource($securityLog);
    }

    public function store(Request $request)
    {
        $request->validate(SecurityLog::rules());

        $securityLog = new SecurityLog;
        $fields = SensitiveDataHelper::parseArray($request->json()->all());
        $securityLog->fill($fields);
        $securityLog->saveOrFail();

        return new SecurityLogs($securityLog->refresh());
    }

    private function download(Request $request, User $user = null)
    {
        $request->validate([
            'format' => 'required|string|in:xml,text'
        ]);
        sleep(1);
        $sessionUser = Auth::user();
        DownloadSecurityLog::dispatch($sessionUser, $request->input('format'), $user ? $user->id : null)
            ->delay(now()->addSeconds(5));
        return response()->json([
            'message' => __('The log file is being prepared and will be sent to your email as soon as it is ready.')
        ], 200);
    }

    public function downloadForAllUsers(Request $request)
    {
        return $this->download($request);
    }

    public function downloadForUser(Request $request, User $user)
    {
        return $this->download($request, $user);
    }
}
