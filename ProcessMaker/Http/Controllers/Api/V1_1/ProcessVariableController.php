<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;

class ProcessVariableController extends Controller
{
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
     *         required=true,
     *         description="Comma-separated list of process IDs",
     *         @OA\Schema(type="string", example="1,2,3")
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
            'processIds' => 'required|string',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'savedSearchId' => 'sometimes|string',
        ]);

        // Parse process IDs
        $processIds = array_map('intval', explode(',', $validated['processIds']));
        $perPage = $validated['per_page'] ?? 20;
        $page = $validated['page'] ?? 1;
        $excludeSavedSearch = $validated['savedSearchId'] ?? 0;

        // Generate mock data
        $mockData = $this->generateMockData($processIds);
        if ($excludeSavedSearch) {
            $savedSearch = SavedSearch::find($excludeSavedSearch);
            $columns = $savedSearch->current_columns;
            $mockData = $mockData->filter(function ($variable) use ($columns) {
                return !$columns->pluck('field')->contains($variable['field']);
            });
        }

        // Create paginator
        $paginator = new LengthAwarePaginator(
            $mockData->forPage($page, $perPage),
            $mockData->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        );

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
                ]
            ]
        ]);
    }

    private function generateMockData(array $processIds): Collection
    {
        // Create a cache key based on process IDs
        $cacheKey = 'process_variables_' . implode('_', $processIds);

        // Try to get variables from cache first
        $variables = Cache::remember($cacheKey, now()->addSeconds(5), function () use ($processIds) {
            $variables = collect();

            foreach ($processIds as $processId) {
                // Generate 10 variables per process
                for ($i = 1; $i <= 10; $i++) {
                    $variables->push([
                        'id' => $variables->count() + 1,
                        'process_id' => $processId,
                        'uuid' => (string) Str::uuid(),
                        'format' => $this->getRandomDataType(),
                        'label' => "Variable {$i} for Process {$processId}",
                        'field' => "data.var_{$processId}_{$i}",
                        'asset' => [
                            'id' => "asset_{$processId}_{$i}",
                            'type' => $this->getRandomAssetType(),
                            'name' => "Asset {$i} for Process {$processId}",
                            'uuid' => (string) Str::uuid(),
                        ],
                        'default' => null,
                        'created_at' => now()->toIso8601String(),
                        'updated_at' => now()->toIso8601String(),
                    ]);
                }
            }

            return $variables;
        });

        return $variables;
    }

    private function getRandomDataType(): string
    {
        return collect(['string', 'number', 'boolean', 'array'])->random();
    }

    private function getRandomAssetType(): string
    {
        return collect(['sensor', 'actuator', 'controller', 'device'])->random();
    }
}