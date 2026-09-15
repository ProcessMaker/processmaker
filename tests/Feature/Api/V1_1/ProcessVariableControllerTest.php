<?php

namespace Tests\Feature\Api\V1_1;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ProcessMaker\Http\Controllers\Api\V1_1\ProcessVariableController;
use ProcessMaker\Models\Column;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use ProcessMaker\Package\VariableFinder\Models\AssetVariable;
use ProcessMaker\Package\VariableFinder\Models\ProcessVariable;
use ProcessMaker\Package\VariableFinder\Models\VarFinderVariable;
use ProcessMaker\Services\ProcessScreenVariableService;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessVariableControllerTest extends TestCase
{
    use RequestHelper;

    private bool $isVariablesFinderEnabled;

    /**
     * Set up Variable Finder fixtures after the test user exists.
     *
     * Named withUserSetup so TestCase does not run it before the database
     * snapshot (methods starting with "setup" are auto-invoked too early).
     *
     * @return void
     */
    public function withUserSetup()
    {
        // Check if the VariableFinder package is enabled
        $this->isVariablesFinderEnabled = class_exists(ProcessVariable::class) && Schema::hasTable('process_variables');

        // Clear process variables cache
        $this->clearCache([1, 2, 3]);
        $this->clearCache([1, 2]);

        // Create the processes variables
        if (!$this->isVariablesFinderEnabled) {
            // Mock the ProcessVariableController to use mock data instead of VariableFinder package
            ProcessVariableController::mock(true);
            ProcessVariableController::useVarFinder(false);
            $this->mockVariableFinder([1, 2, 3], null);
            $this->mockVariableFinder([1, 2], null);
        } else {
            ProcessVariableController::mock(false);
            ProcessVariableController::useVarFinder(true);
            $this->loadVariableFinderData([1, 2, 3]);
        }
    }

    /**
     * Test successful variables retrieval with pagination
     */
    public function test_can_get_process_variables_with_pagination(): void
    {
        // Make request to the endpoint
        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1,2,3&page=1&per_page=15');

        // Assert response structure and status
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'process_id',
                        'format',
                        'label',
                        'field',
                        'default',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        // Assert pagination works correctly
        $responseData = $response->json();
        $this->assertEquals(15, $responseData['meta']['per_page']);
        $this->assertEquals(1, $responseData['meta']['current_page']);

        // Since we're generating 10 variables per process (3 processes = 30 total)
        $this->assertEquals(30, $responseData['meta']['total']);
    }

    /**
     * Test successful variables retrieval with pagination without us
     */
    public function test_can_get_process_variables_from_process_screens_with_pagination(): void
    {
        ProcessVariableController::mock(false);
        ProcessVariableController::useVarFinder(false);

        $bpmn = file_get_contents(base_path('tests/Feature/Api/bpmnPatterns/SimpleTaskProcess.bpmn'));
        ProcessVariableController::mock(false);
        $screen1 = $this->createScreenWithFields(1, 10);
        $screen2 = $this->createScreenWithFields(2, 10);
        $screen3 = $this->createScreenWithFields(3, 10);
        $processIds = [];
        $processIds[] = Process::factory()->create([
            'bpmn' => str_replace('pm:screenRef="2"', 'pm:screenRef="' . $screen1->id . '"', $bpmn),
        ])->id;
        $processIds[] = Process::factory()->create([
            'bpmn' => str_replace('pm:screenRef="2"', 'pm:screenRef="' . $screen2->id . '"', $bpmn),
        ])->id;
        $processIds[] = Process::factory()->create([
            'bpmn' => str_replace('pm:screenRef="2"', 'pm:screenRef="' . $screen3->id . '"', $bpmn),
        ])->id;
        $route = route('api.1.1.process_variables.index', [
            'processIds' => implode(',', $processIds),
            'page' => 1,
            'per_page' => 15,
        ]);

        // Make request to the endpoint
        $response = $this->apiCall('GET', $route);

        // Assert response structure and status
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        //'id',
                        //'process_id',
                        'format',
                        'label',
                        'field',
                        'default',
                        //'created_at',
                        //'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        // Assert pagination works correctly
        $responseData = $response->json();
        $this->assertEquals(15, $responseData['meta']['per_page']);
        $this->assertEquals(1, $responseData['meta']['current_page']);

        // Since we're generating 10 variables per process (3 processes = 30 total)
        $this->assertEquals(30, $responseData['meta']['total']);
    }

    private function mockVariableFinder(array $processIds, $excludeSavedSearch)
    {
        // Create a cache key based on process IDs
        $cacheKey = 'process_variables_' . implode('_', $processIds);
        if ($excludeSavedSearch) {
            $cacheKey .= '_exclude_saved_search_' . $excludeSavedSearch;
        }

        // Try to get variables from cache first
        $variables = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($processIds) {
            $variables = collect();

            foreach ($processIds as $processId) {
                // Generate 10 variables per process
                for ($i = 1; $i <= 10; $i++) {
                    $variables->push([
                        'id' => $variables->count() + 1,
                        'process_id' => $processId,
                        'format' => $this->getRandomDataType(),
                        'label' => "Variable {$i} for Process {$processId}",
                        'field' => "data.var_{$processId}_{$i}",
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

    private function clearCache(array $processIds)
    {
        $cacheKey = 'process_variables_' . implode('_', $processIds);
        Cache::forget($cacheKey);
    }

    private function getRandomDataType(): string
    {
        return collect(['string', 'int', 'boolean', 'array'])->random();
    }

    private function getRandomAssetType(): string
    {
        return collect(['sensor', 'actuator', 'controller', 'device'])->random();
    }

    private function loadVariableFinderData(array $processIds)
    {
        foreach ($processIds as $processId) {
            $process = Process::factory()->create([
                'id' => $processId,
            ]);
            // 1. Create the AssetVariable record
            $asset = [
                'type' => $this->getRandomAssetType(),
                'uuid' => (string) Str::uuid(),
            ];
            $assetVariable = AssetVariable::create([
                'uuid' => $asset['uuid'],
                'asset_id' => 1,       // Scren id=1
                'process_id' => $processId,
                'asset_type' => Screen::class,
            ]);

            // 2. Create the ProcessVariable record linking to the AssetVariable
            ProcessVariable::create([
                'uuid' => (string) Str::uuid(),
                'process_id' => $processId,
                'asset_variable_id' => $assetVariable->id,
            ]);

            // Generate 10 variables per process
            for ($i = 1; $i <= 10; $i++) {
                // Generate data similarly to mockVariableFinder
                $format = $this->getRandomDataType();
                $label = "Variable {$i} for Process {$processId}";
                $field = "var_{$processId}_{$i}";

                // 3. Create the VarFinderVariable record linked to the same AssetVariable
                VarFinderVariable::create([
                    'uuid' => (string) Str::uuid(),
                    'asset_variable_id' => $assetVariable->id,
                    'data_type' => $format,
                    'label' => $label,
                    'field' => $field,
                ]);
            }
        }
    }

    /**
     * Test validation for required processIds parameter
     */
    public function test_process_ids_are_not_required(): void
    {
        $response = $this->apiCall('GET', '/api/1.1/processes/variables');

        $response->assertStatus(200);
    }

    /**
     * Test validation for per_page parameter
     */
    public function test_per_page_validation(): void
    {
        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * Test data consistency across pages
     */
    public function test_pagination_consistency(): void
    {
        // Get first page
        $firstPage = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&page=1&per_page=5')
            ->json();

        // Get second page
        $secondPage = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&page=2&per_page=5')
            ->json();

        // Ensure no duplicate IDs between pages
        $firstPageIds = collect($firstPage['data'])->pluck('id');
        $secondPageIds = collect($secondPage['data'])->pluck('id');

        $this->assertEquals(0, $firstPageIds->intersect($secondPageIds)->count());
    }

    /**
     * Test that process IDs filter works correctly
     */
    public function test_process_ids_filtering(): void
    {
        ProcessVariableController::mock(true);
        $this->mockVariableFinder([1, 2, 3], null);
        $this->mockVariableFinder([1, 2], null);

        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1,2&per_page=50');

        $responseData = $response->json();

        // Check that only requested process IDs are returned
        $uniqueProcessIds = collect($responseData['data'])
            ->pluck('process_id')
            ->unique()
            ->values()
            ->all();

        $this->assertEquals([1, 2], $uniqueProcessIds);

        // Since we generate 10 variables per process, total should be 20
        $this->assertEquals(20, $responseData['meta']['total']);
    }

    /**
     * Test filtering with savedSearchId parameter
     */
    public function test_saved_search_id_filtering(): void
    {
        // Create a saved search with specific columns
        $savedSearch = SavedSearch::factory()->create([
            'meta' => [
                'columns' => [
                    [
                        'label' => 'Variable 1',
                        'field' => 'data.var_1_1',
                        'default' => null,
                    ],
                    [
                        'label' => 'Variable 2',
                        'field' => 'data.var_1_2',
                        'default' => null,
                    ],
                ],
            ],
        ]);

        // Make request with savedSearchId
        if (!$this->isVariablesFinderEnabled) {
            $this->mockVariableFinder([1], $savedSearch->id);
        }
        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&savedSearchId=' . $savedSearch->id);

        $responseData = $response->json();

        // Check that the filtered variables do not include the fields from the saved search
        $filteredFields = collect($responseData['data'])->pluck('field');

        $this->assertFalse($filteredFields->contains('data.var_1_1'));
        $this->assertFalse($filteredFields->contains('data.var_1_2'));

        // The two active fields are excluded from the available variable pages.
        $this->assertEquals(8, $responseData['meta']['total']);
    }

    /**
     * Create a screen with a given number of fields
     *
     * @param int $processId
     * @param int $fieldsCount
     *
     * @return Screen
     */
    private function createScreenWithFields(int $processId, int $fieldsCount)
    {
        $items = [];
        for ($i = 1; $i <= $fieldsCount; $i++) {
            $items[] = [
                'component' => 'FormInput',
                'config' => [
                    'name' => "var_{$processId}_{$i}",
                    'type' => 'text',
                    'label' => "Variable {$i} for Process {$processId}",
                    'helper' => null,
                    'dataFormat' => 'string',
                    'validation' => null,
                    'placeholder' => null,
                ],
            ];
        }

        return Screen::factory()->create([
            'config' => [
                [
                    [
                        'name' => 'screen name',
                        'items' => $items,
                    ],
                ],
            ],
        ]);
    }

    public function test_saved_search_with_all_available_columns(): void
    {
        ProcessVariableController::mock(false);
        // Create a saved search with specific columns
        $savedSearch = SavedSearch::factory()->create([
            'type' => 'request',
            'meta' => [
                'icon' => 'bath',
                'file' => null,
                'collection_id' => null,
                'columns' => [],
            ],
            'pmql' => '',
        ]);

        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&savedSearchId=' . $savedSearch->id . '&onlyAvailable=');

        $responseData = $response->json();

        $filteredFields = collect($responseData['data'])->pluck('field');

        $this->assertTrue($filteredFields->contains('case_number'));
        $this->assertTrue($filteredFields->contains('case_title'));
        $this->assertTrue($filteredFields->contains('name'));
        $this->assertTrue($filteredFields->contains('active_tasks'));
        $this->assertTrue($filteredFields->contains('process_version_alternative'));
        $this->assertTrue($filteredFields->contains('participants'));
        $this->assertTrue($filteredFields->contains('status'));
        $this->assertTrue($filteredFields->contains('initiated_at'));
        $this->assertTrue($filteredFields->contains('completed_at'));
    }

    public function test_fulltext_saved_search_with_empty_process_ids_loads_available_columns(): void
    {
        ProcessVariableController::mock(false);
        ProcessVariableController::useVarFinder(false);
        Setting::updateOrCreate(
            ['key' => 'indexed-search'],
            ['config' => ['enabled' => false]]
        );

        $process = Process::factory()->create();
        ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'data' => ['four_33197_variable' => 'value'],
        ]);
        $savedSearch = SavedSearch::factory()->create([
            'type' => SavedSearch::TYPE_REQUEST,
            'meta' => [
                'icon' => 'search',
                'columns' => [],
            ],
            'pmql' => '(fulltext LIKE "%test%")',
        ]);

        $response = $this->apiCall(
            'GET',
            '/api/1.1/processes/variables?processIds=&savedSearchId=' . $savedSearch->id . '&onlyAvailable='
        );

        $response->assertStatus(200);
        $fields = collect($response->json('data'))->pluck('field');
        $this->assertContains('case_number', $fields);
        $this->assertContains('data.four_33197_variable', $fields);
    }

    public function test_only_available_columns_are_paginated_without_losing_fields(): void
    {
        ProcessVariableController::mock(false);
        ProcessVariableController::useVarFinder(false);

        $process = Process::factory()->create();
        $data = collect(range(1, 125))->mapWithKeys(function ($index) {
            return ["four_33197_paginated_{$index}" => 'value'];
        })->all();
        ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'data' => $data,
        ]);
        $savedSearch = SavedSearch::factory()->create([
            'type' => SavedSearch::TYPE_REQUEST,
            'meta' => [
                'icon' => 'search',
                'columns' => [
                    [
                        'label' => 'Active paginated variable',
                        'field' => 'data.four_33197_paginated_1',
                    ],
                ],
            ],
            'pmql' => '',
        ]);
        $url = '/api/1.1/processes/variables?processIds=' . $process->id
            . '&savedSearchId=' . $savedSearch->id
            . '&onlyAvailable=&per_page=50&page=';

        $firstPage = $this->apiCall('GET', $url . '1');
        $firstPage->assertStatus(200);
        $total = $firstPage->json('meta.total');
        $lastPage = $firstPage->json('meta.last_page');
        $fields = collect($firstPage->json('data'))->pluck('field');

        $this->assertGreaterThan(100, $total);
        $this->assertCount(50, $firstPage->json('data'));
        $this->assertSame(50, $firstPage->json('meta.per_page'));
        $this->assertSame(1, $firstPage->json('meta.from'));
        $this->assertSame(50, $firstPage->json('meta.to'));
        $this->assertSame((int) ceil($total / 50), $lastPage);

        for ($page = 2; $page <= $lastPage; $page++) {
            $response = $this->apiCall('GET', $url . $page);
            $expectedCount = min(50, $total - (($page - 1) * 50));

            $response->assertStatus(200);
            $this->assertCount($expectedCount, $response->json('data'));
            $this->assertSame($page, $response->json('meta.current_page'));
            $this->assertSame($total, $response->json('meta.total'));
            $this->assertSame((($page - 1) * 50) + 1, $response->json('meta.from'));
            $this->assertSame(($page - 1) * 50 + $expectedCount, $response->json('meta.to'));
            $fields = $fields->merge(collect($response->json('data'))->pluck('field'));
        }

        $this->assertCount($total, $fields);
        $this->assertCount($total, $fields->unique());
        $this->assertNotContains('data.four_33197_paginated_1', $fields);
        foreach (range(2, 125) as $index) {
            $this->assertContains("data.four_33197_paginated_{$index}", $fields);
        }
    }

    public function test_process_screen_variable_pages_exclude_active_columns(): void
    {
        ProcessVariableController::mock(false);
        ProcessVariableController::useVarFinder(false);

        $bpmn = file_get_contents(base_path('tests/Feature/Api/bpmnPatterns/SimpleTaskProcess.bpmn'));
        $screen = $this->createScreenWithFields(91, 12);
        $process = Process::factory()->create([
            'bpmn' => str_replace('pm:screenRef="2"', 'pm:screenRef="' . $screen->id . '"', $bpmn),
        ]);
        $savedSearch = SavedSearch::factory()->create([
            'type' => SavedSearch::TYPE_REQUEST,
            'meta' => [
                'icon' => 'search',
                'columns' => [
                    [
                        'label' => 'Variable 1 for Process 91',
                        'field' => 'data.var_91_1',
                    ],
                ],
            ],
            'pmql' => '',
        ]);
        $url = '/api/1.1/processes/variables?processIds=' . $process->id
            . '&savedSearchId=' . $savedSearch->id
            . '&per_page=5&page=';
        $fields = collect();

        for ($page = 1; $page <= 3; $page++) {
            $response = $this->apiCall('GET', $url . $page);

            $response->assertStatus(200);
            $this->assertLessThanOrEqual(5, count($response->json('data')));
            $this->assertSame(11, $response->json('meta.total'));
            $fields = $fields->merge(collect($response->json('data'))->pluck('field'));
        }

        $this->assertCount(11, $fields);
        $this->assertCount(11, $fields->unique());
        $this->assertNotContains('data.var_91_1', $fields);
    }

    public function test_only_available_without_saved_search_returns_an_empty_typed_page(): void
    {
        $response = $this->apiCall(
            'GET',
            '/api/1.1/processes/variables?processIds=1&page=2&per_page=7&onlyAvailable='
        );

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'));
        $this->assertSame(2, $response->json('meta.current_page'));
        $this->assertSame(7, $response->json('meta.per_page'));
        $this->assertSame(0, $response->json('meta.total'));
        $this->assertSame(1, $response->json('meta.last_page'));
        $this->assertNull($response->json('meta.from'));
        $this->assertNull($response->json('meta.to'));
        $this->assertNull($response->json('meta.links.next'));
    }

    public function test_only_available_out_of_range_page_preserves_pagination_query(): void
    {
        $savedSearch = SavedSearch::factory()->create([
            'type' => SavedSearch::TYPE_REQUEST,
            'meta' => ['columns' => []],
            'pmql' => '',
        ]);
        $response = $this->apiCall(
            'GET',
            '/api/1.1/processes/variables?processIds=1&savedSearchId=' . $savedSearch->id
                . '&page=99&per_page=4&onlyAvailable=1'
        );

        $response->assertStatus(200);
        $total = $response->json('meta.total');
        $this->assertGreaterThan(0, $total);
        $this->assertSame([], $response->json('data'));
        $this->assertSame(99, $response->json('meta.current_page'));
        $this->assertSame(4, $response->json('meta.per_page'));
        $this->assertSame((int) ceil($total / 4), $response->json('meta.last_page'));
        $this->assertNull($response->json('meta.from'));
        $this->assertNull($response->json('meta.to'));
        $this->assertNull($response->json('meta.links.next'));

        parse_str(parse_url($response->json('meta.links.prev'), PHP_URL_QUERY), $previousQuery);
        $this->assertSame('98', $previousQuery['page']);
        $this->assertSame((string) $savedSearch->id, $previousQuery['savedSearchId']);
        $this->assertSame('1', $previousQuery['onlyAvailable']);
        $this->assertSame('4', $previousQuery['per_page']);
    }

    public function test_only_available_does_not_execute_variable_finder_query(): void
    {
        $savedSearch = SavedSearch::factory()->create([
            'type' => SavedSearch::TYPE_REQUEST,
            'meta' => ['columns' => []],
            'pmql' => '',
        ]);
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $response = $this->apiCall(
                'GET',
                '/api/1.1/processes/variables?processIds=1&savedSearchId=' . $savedSearch->id
                    . '&page=1&per_page=5&onlyAvailable='
            );
            $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        } finally {
            DB::disableQueryLog();
        }

        $response->assertStatus(200);
        $this->assertStringNotContainsString('var_finder_variables', strtolower($queries));
    }

    public function test_active_default_column_does_not_hide_namespaced_variable(): void
    {
        if (!$this->isVariablesFinderEnabled) {
            $this->markTestSkipped('Variable Finder is not enabled.');
        }

        $assetVariable = AssetVariable::where('process_id', 1)->firstOrFail();
        VarFinderVariable::create([
            'uuid' => (string) Str::uuid(),
            'asset_variable_id' => $assetVariable->id,
            'data_type' => 'string',
            'label' => 'Variable named like a default column',
            'field' => 'case_number',
        ]);
        $savedSearch = SavedSearch::factory()->create([
            'type' => SavedSearch::TYPE_REQUEST,
            'meta' => [
                'columns' => [[
                    'label' => 'Case Number',
                    'field' => 'case_number',
                ]],
            ],
            'pmql' => '',
        ]);

        $response = $this->apiCall(
            'GET',
            '/api/1.1/processes/variables?processIds=1&savedSearchId=' . $savedSearch->id . '&per_page=100'
        );

        $response->assertStatus(200);
        $this->assertContains('data.case_number', collect($response->json('data'))->pluck('field'));
    }

    public function test_variable_finder_sort_memory_error_falls_back_to_screen_variables(): void
    {
        if (!$this->isVariablesFinderEnabled) {
            $this->markTestSkipped('Variable Finder is not enabled.');
        }

        ProcessVariableController::mock(false);
        ProcessVariableController::useVarFinder(true);
        $this->mock(ProcessScreenVariableService::class, function ($mock) {
            $mock->shouldReceive('forProcesses')
                ->once()
                ->andReturn(collect([
                    new Column([
                        'label' => 'Fallback Field',
                        'field' => 'data.fallback_field',
                        'sortable' => true,
                        'default' => false,
                        'format' => 'string',
                        'mask' => null,
                    ]),
                ]));
        });
        DB::beforeExecuting(function ($query, $bindings) {
            if (!str_contains($query, 'var_finder_variables')) {
                return;
            }

            $previous = new \PDOException('Out of sort memory', 1038);
            $previous->errorInfo = ['HY001', 1038, 'Out of sort memory'];

            throw new QueryException('processmaker', $query, $bindings, $previous);
        });

        $response = $this->apiCall(
            'GET',
            '/api/1.1/processes/variables?processIds=1&page=1&per_page=5'
        );

        $response->assertStatus(200);
        $this->assertSame(['data.fallback_field'], collect($response->json('data'))->pluck('field')->all());
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame(5, $response->json('meta.per_page'));
    }

    public function test_saved_search_with_remaining_available_columns(): void
    {
        ProcessVariableController::mock(false);
        // Create a saved search with specific columns
        $savedSearch = SavedSearch::factory()->create([
            'type' => 'request',
            'meta' => [
                'icon' => 'bath',
                'file' => null,
                'collection_id' => null,
                'columns' => [
                    [
                        'label' => 'Case Number',
                        'field' => 'case_number',
                    ],
                    [
                        'label' => 'Case Title',
                        'field' => 'case_title',
                    ],
                ],
            ],
            'pmql' => '',
        ]);

        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&savedSearchId=' . $savedSearch->id . '&onlyAvailable=');

        $responseData = $response->json();

        $filteredFields = collect($responseData['data'])->pluck('field');

        $this->assertFalse($filteredFields->contains('case_number'));
        $this->assertFalse($filteredFields->contains('case_title'));

        $this->assertTrue($filteredFields->contains('name'));
        $this->assertTrue($filteredFields->contains('active_tasks'));
        $this->assertTrue($filteredFields->contains('process_version_alternative'));
        $this->assertTrue($filteredFields->contains('participants'));
        $this->assertTrue($filteredFields->contains('status'));
        $this->assertTrue($filteredFields->contains('initiated_at'));
        $this->assertTrue($filteredFields->contains('completed_at'));
    }

    public function test_saved_search_with_no_available_columns(): void
    {
        // Create a saved search with specific columns
        $savedSearch = SavedSearch::factory()->create([
            'type' => 'request',
            'meta' => [
                'icon' => 'bath',
                'file' => null,
                'collection_id' => null,
                'columns' => [
                    [
                        'label' => 'Case Number',
                        'field' => 'case_number',
                    ],
                    [
                        'label' => 'Case Title',
                        'field' => 'case_title',
                    ],
                    [
                        'label' => 'Name',
                        'field' => 'name',
                    ],
                    [
                        'label' => 'Active Tasks',
                        'field' => 'active_tasks',
                    ],
                    [
                        'label' => 'Process Version Alternative',
                        'field' => 'process_version_alternative',
                    ],
                    [
                        'label' => 'Participants',
                        'field' => 'participants',
                    ],
                    [
                        'label' => 'Status',
                        'field' => 'status',
                    ],
                    [
                        'label' => 'Initiated At',
                        'field' => 'initiated_at',
                    ],
                    [
                        'label' => 'Completed At',
                        'field' => 'completed_at',
                    ],
                ],
            ],
            'pmql' => '',
        ]);

        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&savedSearchId=' . $savedSearch->id . '&onlyAvailable=');

        $responseData = $response->json();

        $filteredFields = collect($responseData['data'])->pluck('field');

        $this->assertFalse($filteredFields->contains('case_number'));
        $this->assertFalse($filteredFields->contains('case_title'));
        $this->assertFalse($filteredFields->contains('name'));
        $this->assertFalse($filteredFields->contains('active_tasks'));
        $this->assertFalse($filteredFields->contains('process_version_alternative'));
        $this->assertFalse($filteredFields->contains('participants'));
        $this->assertFalse($filteredFields->contains('status'));
        $this->assertFalse($filteredFields->contains('initiated_at'));
        $this->assertFalse($filteredFields->contains('completed_at'));
    }
}
