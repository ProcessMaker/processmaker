<?php

namespace ProcessMaker\Http\Controllers\Api;

use DB;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Jobs\ImportSettings;
use ProcessMaker\Models\Setting;
use Throwable;

class SettingController extends Controller
{
    private $fields = [
        'id',
        'key',
        'config',
        'name',
        'helper',
        'group',
        'format',
        'created_at',
        'updated_at',
    ];

    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [];

    public function groups(Request $request)
    {
        $query = Setting::query();

        $query->select('group')->groupBy('group');

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $query->filterGroups($filter);
        }

        $pmql = $request->input('pmql', '');
        if (!empty($pmql)) {
            try {
                $query->pmql($pmql);
            } catch (\ProcessMaker\Query\SyntaxError $e) {
                return response(['error' => 'PMQL error'], 400);
            }
        }

        $query->notHidden();

        $orderBy = 'group';
        $orderDirection = 'ASC';

        if($request->has('order_by') && in_array($request->input('order_by'), $this->fields)){
          $orderBy = $request->input('order_by');
        }

        if($request->has('order_direction')){
          $orderDirection = $request->input('order_direction');
        }

        $response = $query->orderBy($orderBy, $orderDirection)
            ->paginate($request->input('per_page', 1000));

        return new ApiCollection($response);
    }

    public function buttons($group)
    {
        $buttons = Setting::where('group', $group)
            ->where('hidden', true)
            ->where('format', 'button')
            ->get()
            ->toArray();
        foreach($buttons as $i => $button) {
            $buttons[$i]['ui'] = \is_string($button['ui']) ? json_decode($button['ui']) : $button['ui'];
        }
        return $buttons;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     *
     *     @OA\Get(
     *     path="/settings",
     *     summary="Returns all settings",
     *     operationId="getSettings",
     *     tags={"Settings"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of settings",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/settings"),
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
        $query = Setting::query();

        $group = $request->input('group');
        if (!empty($group)) {
            if ($group === 'System') {
                $query->whereNull('group');
            } else {
                $query->where('group', $group);
            }
        }

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $query->filter($filter);
        }

        $pmql = $request->input('pmql', '');
        if (!empty($pmql)) {
            try {
                $query->pmql($pmql);
            } catch (\ProcessMaker\Query\SyntaxError $e) {
                return response(['error' => 'PMQL error'], 400);
            }
        }

        $query->notHidden();

        $orderBy = 'name';
        $orderDirection = 'ASC';

        if ($request->has('order_by') && in_array($request->input('order_by'), $this->fields)){
            $orderBy = $request->input('order_by');
        }

        if ($request->has('order_direction')){
            $orderDirection = $request->input('order_direction');
        }

        $response = $query->orderBy(DB::raw("CAST(ui->>'$.order' AS UNSIGNED)"), 'asc')->orderBy($orderBy, $orderDirection)
            ->paginate($request->input('per_page', 25));

        return new ApiCollection($response);
    }

    /**
     * Update a setting
     *
     * @param Setting $setting
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Put(
     *     path="/settings/{setting_id}",
     *     summary="Update a setting",
     *     operationId="updateSetting",
     *     tags={"Settings"},
     *     @OA\Parameter(
     *         description="ID of setting to return",
     *         in="path",
     *         name="setting_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/settingsEditable")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     * )
     */
    public function update(Setting $setting, Request $request)
    {
        $setting->config = $request->input('config');
        $setting->save();

        return response([], 204);
    }

    public function import(Request $request)
    {
        $content = $request->file('file')->get();

        try {
            $imported = ImportSettings::dispatchNow($content);
        } catch (Throwable $e) {
            return response([
                'error' => $e->getMessage(),
            ], 400);
        }

        return [
            'data' => $imported,
            'meta' => [
                'count' => $imported->count(),
                'total' => $imported->count(),
            ],
        ];
    }
}
