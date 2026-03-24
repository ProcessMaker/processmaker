<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\CaseRetentionPolicyLog;

class CasesRetentionController extends Controller
{
    private const LOG_SORT_COLUMNS = [
        'id',
        'process_id',
        'case_ids',
        'deleted_count',
        'total_time_taken',
        'deleted_at',
        'created_at',
    ];

    public function logs(Request $request): ApiCollection
    {
        $query = CaseRetentionPolicyLog::query();

        if ($filter = $request->input('filter')) {
            $filter = '%' . mb_strtolower($filter) . '%';
            $query->where('process_id', 'like', $filter);
        }

        $orderBy = $request->input('order_by');
        if ($orderBy && in_array($orderBy, self::LOG_SORT_COLUMNS, true)) {
            $orderBy = DB::raw(preg_replace('/\.(.+)/', "->>'\$.$1'", $orderBy, 1));

            $orderDirection = strtolower((string) $request->input('order_direction', 'asc'));
            if (!in_array($orderDirection, ['asc', 'desc'], true)) {
                $orderDirection = 'asc';
            }

            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderByDesc('created_at');
        }

        $response = $query->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }
}
