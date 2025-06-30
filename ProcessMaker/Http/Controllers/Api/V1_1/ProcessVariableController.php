<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use ProcessMaker\Package\VariableFinder\Models\ProcessVariable;

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
        $perPage = $validated['per_page'] ?? 20;
        $page = $validated['page'] ?? 1;
        $excludeSavedSearch = $validated['savedSearchId'] ?? 0;

        // Generate mock data
        if (static::$mockData) {
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
     * @return \Illuminate\Http\JsonResponse JSON response containing the process variables.
     */
    public function getProcessesVariables(array $processIds, $excludeSavedSearch, $page, $perPage, $request)
    {
        // Determine which columns to exclude based on the saved search
        $activeColumns = [];
        $savedSearch = null;
        if ($excludeSavedSearch) {
            $savedSearch = SavedSearch::find($excludeSavedSearch);
            if ($savedSearch && $savedSearch->current_columns) {
                $activeColumns = $savedSearch->current_columns->pluck('field')->toArray();
            }
        }

        // If the classes or tables do not exist, fallback to a saved search approach.
        if (
            !class_exists(ProcessVariable::class)
            || !Schema::hasTable('process_variables')
            || !self::$useVarFinder
        ) {
            $paginator = $this->getProcessesVariablesFrom($processIds);
            if ($request->has('onlyAvailable')) {
                return $this->mergeOnlyAvailableColumns($paginator, $savedSearch, $activeColumns);
            }

            return $paginator;
        }

        // Build a single query that joins asset_variables, and var_finder_variables
        // and applies filtering for excluded fields.
        $query = DB::table('asset_variables as av')
            ->join('var_finder_variables as vfv', 'av.id', '=', 'vfv.asset_variable_id')
            ->whereIn('av.process_id', $processIds)
            ->groupBy('vfv.field', 'vfv.label')
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

        if (!empty($activeColumns)) {
            $activeColumns = array_map(function ($column) {
                return preg_replace('/^data\./', '', $column);
            }, $activeColumns);
            $query->whereNotIn('vfv.field', $activeColumns);
        }

        // Return the paginated result
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        if ($request->has('onlyAvailable')) {
            return $this->mergeOnlyAvailableColumns($paginator, $savedSearch, $activeColumns);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Merge only available columns with collection items
     *
     * @param LengthAwarePaginator $paginator
     * @param SavedSearch|null $savedSearch
     * @param array $activeColumns
     *
     * @return LengthAwarePaginator
     */
    private function mergeOnlyAvailableColumns($paginator, $savedSearch, $activeColumns)
    {
        $availableColumns = $this->mergeAvailableColumns($savedSearch);
        $availableColumns = $availableColumns->merge($paginator->items());
        $availableColumns = $this->filterActiveColumns($availableColumns, $activeColumns);
        $paginator->setCollection($availableColumns);

        return $paginator;
    }

    /**
     * Merge available columns with collection items
     *
     * @param SavedSearch|null $savedSearch
     */
    private function mergeAvailableColumns(?SavedSearch $savedSearch = null)
    {
        $availableColumns = collect();

        if ($savedSearch?->available_columns) {
            $availableColumns = $savedSearch->available_columns->merge(
                $savedSearch->getDataColumnsAttribute() ?? collect()
            );
        }

        return $availableColumns;
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
     * Retrieve process variables from its screens.
     *
     * @param array $processIds
     *
     * @return LengthAwarePaginator
     */
    private function getProcessesVariablesFrom(array $processIds)
    {
        $perPage = request()->get('per_page', 20);
        // Validate processIds input is required
        if (empty($processIds)) {
            return new LengthAwarePaginator([], 0, $perPage, 1);
        }

        // Get screens used in the processes
        $processes = Process::whereIn('id', $processIds)->get();
        $ids = collect([]);
        foreach ($processes as $process) {
            $manager = app(ExportManager::class);
            try {
                $ids = $ids->merge($manager->getDependenciesOfType(Screen::class, $process));
            } catch (\Exception $e) {
                $ids = collect([]);
            }
        }

        // Get columns from screens
        $columns = collect([]);
        $screens = Screen::whereIn('id', $ids->unique())->where('type', '!=', 'DISPLAY')->get();
        foreach ($screens as $screen) {
            $screenColumns = $screen->fields->map(function ($item) {
                $item->field = "data.{$item->field}";

                return $item;
            });

            $columns = $columns->merge($screenColumns);
        }

        // Paginate the result
        $page = request()->get('page', 1);
        $total = $columns->count();
        $items = $columns->forPage($page, $perPage);

        return new LengthAwarePaginator($items, $total, $perPage, $page);
    }
}
