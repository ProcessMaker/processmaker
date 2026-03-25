<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Search log id, process_id, numeric columns, and JSON case_ids — not date columns.
     */
    private function applyLogsFilter($query, string $term): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }

        $like = '%' . $term . '%';
        $driver = $query->getConnection()->getDriverName();

        $query->where(function ($q) use ($like, $driver) {
            $q->where('id', 'like', $like)
                ->orWhere('process_id', 'like', $like)
                ->orWhere('deleted_count', 'like', $like)
                ->orWhere('total_time_taken', 'like', $like);

            if ($driver === 'pgsql') {
                $q->orWhereRaw('case_ids::text ILIKE ?', [$like]);
            } else {
                $q->orWhereRaw('CAST(case_ids AS CHAR) LIKE ?', [$like]);
            }
        });
    }

    public function logs(Request $request): ApiCollection
    {
        $query = CaseRetentionPolicyLog::query();

        if ($request->filled('filter')) {
            $this->applyLogsFilter($query, (string) $request->input('filter'));
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
