<?php

namespace Tests\Feature\Api\V1_1;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ProcessMaker\Http\Controllers\Api\V1_1\ProcessVariableController;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use ProcessMaker\Package\VariableFinder\Models\AssetVariable;
use ProcessMaker\Package\VariableFinder\Models\ProcessVariable;
use ProcessMaker\Package\VariableFinder\Models\VarFinderVariable;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessVariableControllerTest extends TestCase
{
    use RequestHelper;

    private bool $isVariablesFinderEnabled;

    /**
     * Set up test environment by creating a test user and authenticating as them
     *
     * @return void
     */
    public function setupCreateUser()
    {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

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
                $field = "data.var_{$processId}_{$i}";

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

        // Check that the total count matches the actual number of variables
        $this->assertEquals(10, $responseData['meta']['total']); // Total number of variables
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
