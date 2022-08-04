<?php

namespace ProcessMaker\Http\Controllers\Api;

use DB;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\SecurityLog;

class SecurityLogController extends Controller
{
    /**
     * Get a list of Security Logs.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/security-logs",
     *     summary="Returns all security logs",
     *     operationId="getSecurityLogs",
     *     tags={"Secuirty Logs"},
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
            $filter = '%'.mb_strtolower($filter).'%';
            $query->where('event', 'like', $filter)
                  ->orWhere(DB::raw('LOWER(ip)'), 'like', $filter)
                  ->orWhere(DB::raw("LOWER(meta->>'$.browser.name')"), 'like', $filter)
                  ->orWhere(DB::raw("LOWER(meta->>'$.os.name')"), 'like', $filter);
        }

        if ($orderBy = $request->input('order_by')) {
            $orderBy = DB::raw(preg_replace('/\.(.+)/', "->>'\$.$1'", $orderBy, 1));

            $orderDirection = $request->input('order_direction');

            if (! $orderDirection) {
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
     *     tags={"Secuirty Logs"},
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
}
