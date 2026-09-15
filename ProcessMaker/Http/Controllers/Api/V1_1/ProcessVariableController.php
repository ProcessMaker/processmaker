<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use ProcessMaker\Package\VariableFinder\Models\ProcessVariable;
use ProcessMaker\Services\ProcessVariableDiscoveryService;

class ProcessVariableController extends Controller
{
    const CACHE_TTL = 60;

    private static bool $mockData = false;

    private static bool $useVarFinder = true;

    /**
     * @OA\Schema(
     *     schema="Variable",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="process_id", type="integer", example=1),
     *     @OA\Property(property="uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *     @OA\Property(property="field", type="string", enum={"string", "number", "boolean", "array"}, example="string"),
     *     @OA\Property(property="label", type="string", example="Variable 1 for Process 1"),
     *     @OA\Property(property="name", type="string", example="var_1_1"),
     *     @OA\Property(
     *         property="asset",
     *         type="object",
     *         @OA\Property(property="id", type="string", example="asset_1_1"),
     *         @OA\Property(property="type", type="string", enum={"sensor", "actuator", "controller", "device"}, example="sensor"),
     *         @OA\Property(property="name", type="string", example="Asset 1 for Process 1"),
     *         @OA\Property(property="uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     * @OA\Schema(
     *     schema="PaginationMeta",
     *     type="object",
     *     @OA\Property(property="current_page", type="integer", example=1),
     *     @OA\Property(property="from", type="integer", example=1),
     *     @OA\Property(property="last_page", type="integer", example=5),
     *     @OA\Property(property="path", type="string", example="http://processmaker.com/processes/variables"),
     *     @OA\Property(property="per_page", type="integer", example=20),
     *     @OA\Property(property="to", type="integer", example=20),
     *     @OA\Property(property="total", type="integer", example=100),
     *     @OA\Property(
     *         property="links",
     *         type="object",
     *         @OA\Property(property="first", type="string", example="http://processmaker.com/processes/variables?page=1"),
     *         @OA\Property(property="last", type="string", example="http://processmaker.com/processes/variables?page=5"),
     *         @OA\Property(property="prev", type="string", nullable=true),
     *         @OA\Property(property="next", type="string", example="http://processmaker.com/processes/variables?page=2")
     *     )
     * )
     * @OA\Get(
     *     path="/processes/variables",
     *     summary="Get variables for multiple processes with pagination",
     *     servers={
     *         @OA\Server(url=L5_SWAGGER_API_V1_1, description="API v1.1 Server")
     *     },
     *     tags={"Processes Variables"},
     *     @OA\Parameter(
     *         name="processIds",
     *         in="query",
     *         required=false,
     *         description="Comma-separated list of process IDs",
     *         @OA\Schema(type="string", example="1,2,3", nullable=true)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Variable")
     *             ),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'processIds' => 'sometimes|string|nullable',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'savedSearchId' => 'sometimes|string',
        ]);

        // Parse process IDs
        $processIds = !empty($validated['processIds'])
            ? array_map('intval', explode(',', $validated['processIds']))
            : [];
        $perPage = (int) ($validated['per_page'] ?? 20);
        $page = (int) ($validated['page'] ?? 1);
        $excludeSavedSearch = $validated['savedSearchId'] ?? 0;

        // Available columns and process variables have independent pagination.
        if ($request->has('onlyAvailable')) {
            $paginator = $this->getAvailableColumnsPaginator(
                $excludeSavedSearch,
                $page,
                $perPage,
                $request
            );
        } elseif (static::$mockData) {
            $paginator = $this->getProcessesVariablesFromMock($processIds, $excludeSavedSearch, $page, $perPage, $request);
        } else {
            $paginator = $this->getProcessesVariables($processIds, $excludeSavedSearch, $page, $perPage, $request);
        }

        return response()->json([
            'data' => array_values($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
        ]);
    }

    /**
     * Retrieve process variables from a mock source.
     *
     * @param array $processIds An array of process IDs to retrieve variables for.
     * @param bool $excludeSavedSearch Flag to determine whether to exclude saved searches.
     * @param int $page The page number for pagination.
     * @param int $perPage The number of items per page for pagination.
     * @param Request $request The HTTP request instance.
     *
     * @return array The list of process variables.
     */
    private function getProcessesVariablesFromMock(array $processIds, $excludeSavedSearch, $page, $perPage, $request)
    {
        $cacheKey = 'process_variables_' . implode('_', $processIds);
        if ($excludeSavedSearch) {
            $cacheKey .= '_exclude_saved_search_' . $excludeSavedSearch;
        }

        $mockData = Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL), function () use ($excludeSavedSearch) {
            if (!$excludeSavedSearch) {
                return collect();
            }

            $savedSearch = SavedSearch::find($excludeSavedSearch);

            return collect(array_values($savedSearch->data_columns->toArray()));
        });

        if ($excludeSavedSearch) {
            $savedSearch = SavedSearch::find($excludeSavedSearch);
            $columns = $savedSearch->current_columns;
            $mockData = $mockData->filter(function ($variable) use ($columns) {
                return !$columns->pluck('field')->contains($variable['field']);
            });
        }

        // Create paginator
        return new LengthAwarePaginator(
            $mockData->forPage($page, $perPage),
            $mockData->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        );
    }

    /**
     * Retrieve process variables for the given process IDs.
     *
     * @param array $processIds Array of process IDs to retrieve variables for.
     * @param bool $excludeSavedSearch Flag to exclude saved searches.
     * @param int $page The page number for pagination.
     * @param int $perPage The number of items per page for pagination.
     * @param Request $request The HTTP request instance.
     * @return LengthAwarePaginator
     */
    public function getProcessesVariables(array $processIds, $excludeSavedSearch, $page, $perPage, $request)
    {
        $activeColumns = [];
        if ($excludeSavedSearch) {
            $savedSearch = SavedSearch::find($excludeSavedSearch);
            if ($savedSearch && $savedSearch->current_columns) {
                $activeColumns = $savedSearch->current_columns->pluck('field')->toArray();
            }
        }

        if (
            !class_exists(ProcessVariable::class)
            || !Schema::hasTable('process_variables')
            || !self::$useVarFinder
            || $processIds === []
        ) {
            return $this->getProcessesVariablesFrom($processIds, $activeColumns, $page, $perPage, $request);
        }

        $query = DB::table('asset_variables as av')
            ->join('var_finder_variables as vfv', 'av.id', '=', 'vfv.asset_variable_id')
            ->whereIn('av.process_id', $processIds)
            ->groupBy('vfv.field', 'vfv.label')
            ->orderBy('vfv.field')
            ->select([
                DB::raw('MAX(vfv.id) as id'),
                DB::raw("CONCAT('data.', vfv.field) as field"),
                'vfv.label',
                DB::raw('MAX(vfv.data_type) as format'),
                DB::raw('MAX(vfv.created_at) as created_at'),
                DB::raw('MAX(vfv.updated_at) as updated_at'),
                DB::raw('MAX(av.process_id) as process_id'),
                DB::raw('NULL AS `default`'),
            ]);

        $activeDataColumns = collect($activeColumns)
            ->filter(fn ($column) => is_string($column) && str_starts_with($column, 'data.'))
            ->map(fn ($column) => substr($column, 5))
            ->values()
            ->all();
        if ($activeDataColumns !== []) {
            $query->whereNotIn('vfv.field', $activeDataColumns);
        }

        try {
            return $query->paginate($perPage, ['*'], 'page', $page);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1038) {
                throw $exception;
            }

            Log::warning('Variable Finder exceeded MySQL sort memory; using screen variables', [
                'process_count' => count($processIds),
                'page' => $page,
                'per_page' => $perPage,
            ]);

            return $this->getProcessesVariablesFrom($processIds, $activeColumns, $page, $perPage, $request, false);
        }
    }

    /**
     * Paginate the saved search columns separately from process variables.
     */
    private function getAvailableColumnsPaginator($savedSearchId, int $page, int $perPage, Request $request): LengthAwarePaginator
    {
        $savedSearch = $savedSearchId ? SavedSearch::find($savedSearchId) : null;
        $activeColumns = $savedSearch?->current_columns?->pluck('field')->toArray() ?? [];
        $availableColumns = $this->filterActiveColumns(
            $this->mergeAvailableColumns($savedSearch),
            $activeColumns
        )->unique('field')->values();

        return new LengthAwarePaginator(
            $availableColumns->forPage($page, $perPage)->values(),
            $availableColumns->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * Merge available columns with collection items
     *
     * @param SavedSearch|null $savedSearch
     */
    private function mergeAvailableColumns(?SavedSearch $savedSearch = null)
    {
        if (!$savedSearch) {
            return collect();
        }

        return $savedSearch->available_columns->merge(
            $savedSearch->getDataColumnsAttribute() ?? collect()
        );
    }

    /**
     * Filter out columns that are already active in the saved search
     *
     * @param Collection $availableColumns
     * @param array $activeColumns
     */
    private function filterActiveColumns($availableColumns, $activeColumns)
    {
        return $availableColumns->reject(function ($column) use ($activeColumns) {
            return in_array($column->field, $activeColumns);
        });
    }

    /**
     * Change ProcessVariableController to use mock data
     *
     * @return void
     */
    public static function mock(bool $value = true)
    {
        static::$mockData = $value;
    }

    /**
     * Change ProcessVariableController to not use VariableFinder
     *
     * @return void
     */
    public static function useVarFinder(bool $value = true)
    {
        static::$useVarFinder = $value;
    }

    /**
     * Retrieve process variables from Variable Finder or process screens.
     *
     * @param  list<int>  $processIds
     * @param  list<string>  $activeColumns
     */
    private function getProcessesVariablesFrom(
        array $processIds,
        array $activeColumns,
        int $page,
        int $perPage,
        Request $request,
        ?bool $useVariableFinder = null
    ): LengthAwarePaginator {
        $service = app(ProcessVariableDiscoveryService::class);
        $useVariableFinder = $useVariableFinder ?? self::$useVarFinder;
        $columns = $processIds === []
            ? $service->unscoped($useVariableFinder)
            : $service->forProcessIds($processIds, $useVariableFinder);
        $columns = $this->filterActiveColumns($columns, $activeColumns)
            ->unique('field')
            ->values();

        return new LengthAwarePaginator(
            $columns->forPage($page, $perPage)->values(),
            $columns->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
