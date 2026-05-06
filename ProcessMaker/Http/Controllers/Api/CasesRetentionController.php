<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProcessMaker\CaseRetention\CaseRetentionLogQueryFilter;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Jobs\DownloadCaseRetentionLogExport;
use ProcessMaker\Models\CaseRetentionPolicyLog;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        CaseRetentionLogQueryFilter::applyIfFilled($query, $request->input('filter'));

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

    /**
     * Queue a CSV export to disk; user receives a database + broadcast notification with a signed download URL when ready.
     */
    public function queueExportCsv(Request $request): JsonResponse
    {
        $request->validate([
            'filter' => ['sometimes', 'nullable', 'string'],
        ]);

        $exportToken = (string) Str::uuid();
        DownloadCaseRetentionLogExport::dispatch($request->user(), $request->input('filter'), $exportToken);

        return response()->json([
            'success' => true,
            'message' => __('The file is processing. You may continue working while the log file compiles.'),
        ]);
    }

    /**
     * Signed URL only (no API token). Link is included in the export-ready notification when the job finishes.
     */
    public function downloadExportFile(Request $request, string $token): BinaryFileResponse
    {
        if (!Str::isUuid($token)) {
            abort(404);
        }

        $relativePath = 'exports/case-retention/' . $token . '.csv';
        if (!Storage::disk('local')->exists($relativePath)) {
            abort(404);
        }

        return response()->download(
            Storage::disk('local')->path($relativePath),
            'case_retention_policy_logs.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        )->deleteFileAfterSend(true);
    }
}
