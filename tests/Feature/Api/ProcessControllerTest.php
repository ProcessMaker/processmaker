<?php

namespace ProcessMaker\Api\Tests\Feature;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessControllerTest extends TestCase
{
    use RequestHelper;

    public function testStartProcessesReturnsActiveProcesses()
    {
        $file = Process::getProcessTemplatesPath() . '/SingleTask.bpmn';
        $bpmn = file_get_contents($file);
        // Create categories
        $cat1 = ProcessCategory::factory()->create(['name' => 'A cat', 'status' => 'ACTIVE']);
        $cat2 = ProcessCategory::factory()->create(['name' => 'B cat', 'status' => 'INACTIVE']);
        // Create Processes
        Process::factory()->count(1)->create(['process_category_id' => $cat1->id]);
        Process::factory()->create([
            'name' => 'AProcess',
            'status' => 'ACTIVE',
            'process_category_id' => $cat1->id,
            'bpmn' => $bpmn,
        ]);
        Process::factory()->create([
            'name' => 'BProcess',
            'status' => 'ACTIVE',
            'process_category_id' => $cat1->id,
            'bpmn' => $bpmn,
        ]);
        Process::factory()->create([
            'name' => 'CProcess',
            'status' => 'INACTIVE',
            'process_category_id' => $cat2->id,
        ]);

        $response = $this->apiCall('GET', route('api.processes.start'), ['order_by' => 'category.name,name']);
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['name' => 'AProcess']);
        $response->assertJsonFragment(['name' => 'BProcess']);
        $response->assertJsonMissing(['name' => 'CProcess']);
    }

    public function testStartProcessesFiltersByName()
    {
        $file = Process::getProcessTemplatesPath() . '/SingleTask.bpmn';
        $bpmn = file_get_contents($file);

        // Create categories
        $cat1 = ProcessCategory::factory()->create(['name' => 'A cat', 'status' => 'ACTIVE']);
        // Create Processes
        Process::factory()->create([
            'name' => 'AProcess',
            'status' => 'ACTIVE',
            'process_category_id' => $cat1->id,
            'bpmn' => $bpmn,
        ]);
        Process::factory()->create([
            'name' => 'Test Process B',
            'status' => 'ACTIVE',
            'process_category_id' => $cat1->id,
            'bpmn' => $bpmn,
        ]);
        Process::factory()->create([
            'name' => 'Another Process',
            'status' => 'ACTIVE',
            'process_category_id' => $cat1->id,
            'bpmn' => $bpmn,
        ]);

        $response = $this->apiCall('GET', route('api.processes.start'), ['order_by' => 'category.name,name']);
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        // Filter by completed name
        $response = $this->apiCall('GET', route('api.processes.start'), ['filter' => 'AProcess', 'order_by' => 'category.name,name']);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'AProcess']);

        // Filter by partial name
        $response = $this->apiCall('GET', route('api.processes.start'), ['filter' => 'Test', 'order_by' => 'category.name,name']);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'Test Process B']);

        // Filter by partial name
        $response = $this->apiCall('GET', route('api.processes.start'), ['filter' => 'Process', 'order_by' => 'category.name,name']);
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonFragment(['name' => 'AProcess']);
        $response->assertJsonFragment(['name' => 'Test Process B']);
        $response->assertJsonFragment(['name' => 'Another Process']);
    }

    public function testStartProcessesFiltersByCategory()
    {
        $file = Process::getProcessTemplatesPath() . '/SingleTask.bpmn';
        $bpmn = file_get_contents($file);

        // Create categories
        $cat1 = ProcessCategory::factory()->create(['name' => 'Category A', 'status' => 'ACTIVE']);
        $cat2 = ProcessCategory::factory()->create(['name' => 'Category B', 'status' => 'ACTIVE']);
        // Create Processes
        Process::factory()->create([
            'name' => 'AProcess',
            'status' => 'ACTIVE',
            'process_category_id' => $cat1->id,
            'bpmn' => $bpmn,
        ]);
        Process::factory()->create([
            'name' => 'BProcess',
            'status' => 'ACTIVE',
            'process_category_id' => $cat1->id,
            'bpmn' => $bpmn,
        ]);
        Process::factory()->create([
            'name' => 'CProcess',
            'status' => 'ACTIVE',
            'process_category_id' => $cat2->id,
            'bpmn' => $bpmn,
        ]);

        $response = $this->apiCall('GET', route('api.processes.start'), ['order_by' => 'category.name,name']);
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');

        // Filter by category A
        $response = $this->apiCall('GET', route('api.processes.start'), ['filter' => $cat1->name, 'order_by' => 'category.name,name']);
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['name' => 'AProcess']);
        $response->assertJsonFragment(['name' => 'BProcess']);

        // Filter by category B
        $response = $this->apiCall('GET', route('api.processes.start'), ['filter' => $cat2->name, 'order_by' => 'category.name,name']);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'CProcess']);
    }

    /**
     * Test saving and retrieving stages in the Process model.
     */
    public function testSaveAndRetrieveStages()
    {
        // Define stages
        $stages = [
            ['id' => 101, 'order' => 1, 'name' => 'Request Send', 'selected' => false],
            ['id' => 102, 'order' => 2, 'name' => 'Request Reviewed', 'selected' => false],
            ['id' => 103, 'order' => 3, 'name' => 'Manager Reviewed', 'selected' => false],
        ];

        // Create a new process and save stages as JSON
        $process = Process::factory()->create([
            'name' => 'Sample Stage Process',
            'status' => 'ACTIVE',
            'stages' => json_encode($stages),
        ]);

        // Retrieve the process from the database
        $retrievedProcess = Process::find($process->id);

        // Assert that the stages are correctly retrieved
        $this->assertNotNull($retrievedProcess);
        $this->assertIsString($retrievedProcess->stages);
        $retrievedStages = json_decode($retrievedProcess->stages, true);

        // Assert the content of the stages
        $this->assertEquals($stages, $retrievedStages);
    }

    /**
     * Test getting default stages for a process without stages
     */
    public function testGetDefaultStagesPerProcess()
    {
        $process = Process::factory()->create([
            'name' => 'Test Process Stages',
            'status' => 'ACTIVE',
        ]);

        // Create 20 requests in "In Progress" stage
        ProcessRequest::factory()->count(18)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
        ]);
        // Create 10 requests in "Completed" stage
        ProcessRequest::factory()->count(12)->create([
            'process_id' => $process->id,
            'status' => 'COMPLETED',
        ]);

        $response = $this->apiCall('GET', route('api.processes.default-stages', ['process' => $process->id]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'stage_id',
                    'stage_name',
                    'percentage',
                    'percentage_format',
                    'agregation_sum',
                    'agregation_count',
                ],
            ],
        ]);

        // Verify stages statistics
        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Verify "In Progress" stage
        $inProgress = collect($data)->firstWhere('stage_name', 'In progress');
        $this->assertNotNull($inProgress);
        $this->assertEquals(0, $inProgress['stage_id']);
        $this->assertEquals(60, $inProgress['percentage']);
        $this->assertEquals('60%', $inProgress['percentage_format']);
        $this->assertEquals(0, $inProgress['agregation_sum']);
        $this->assertEquals(18, $inProgress['agregation_count']);

        // Verify "Completed" stage
        $completed = collect($data)->firstWhere('stage_name', 'Completed');
        $this->assertNotNull($completed);
        $this->assertEquals(0, $completed['stage_id']);
        $this->assertEquals(40, $completed['percentage']);
        $this->assertEquals('40%', $completed['percentage_format']);
        $this->assertEquals(0, $completed['agregation_sum']);
        $this->assertEquals(12, $completed['agregation_count']);

        // Verify the percentage needs to sum 100%
        $this->assertEquals(100, $inProgress['percentage'] + $completed['percentage']);
    }

    /**
     * Test getting custom stages for a process with stages and return aggregation amount
     */
    public function testGetStagesPerProcessWithDefaultAggregation()
    {
        $stages = [
            ['id' => 101, 'order' => 1, 'name' => 'Custom Stage 1', 'selected' => false],
            ['id' => 102, 'order' => 2, 'name' => 'Custom Stage 2', 'selected' => false],
        ];

        $process = Process::factory()->create([
            'name' => 'Test Process Custom Stages',
            'status' => 'ACTIVE',
            'stages' => json_encode($stages),
        ]);
        // Create 2 requests without stage
        ProcessRequest::factory()->count(2)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'data' => ['amount' => '10'],
        ]);
        // Create 5 requests in "Custom Stage 1" stage
        ProcessRequest::factory()->count(5)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'last_stage_id' => $stages[0]['id'],
            'last_stage_name' => 'Custom Stage 1',
            'data' => ['amount' => '10'],
        ]);
        // Create 3 requests in "Custom Stage 2" stage
        ProcessRequest::factory()->count(3)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'last_stage_id' => $stages[1]['id'],
            'last_stage_name' => 'Custom Stage 2',
            'data' => ['amount' => '10'],
        ]);

        $response = $this->apiCall('GET', route('api.processes.stages', ['process' => $process->id]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'stage_id',
                    'stage_name',
                    'percentage',
                    'percentage_format',
                    'agregation_sum',
                    'agregation_count',
                ],
            ],
        ]);

        // Verify custom stages
        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Verify "1" stage
        $firstStage = collect($data)->where('stage_id', $stages[0]['id'])->first();
        $this->assertEquals('Custom Stage 1', $firstStage['stage_name']);
        $this->assertEquals(50, $firstStage['agregation_sum']);
        $this->assertEquals(5, $firstStage['agregation_count']);

        // Verify "2" stage
        $secondStage = collect($data)->firstWhere('stage_id', $stages[1]['id']);
        $this->assertEquals('Custom Stage 2', $secondStage['stage_name']);
        $this->assertEquals(30, $secondStage['agregation_sum']);
        $this->assertEquals(3, $secondStage['agregation_count']);

        // Verify the percentage needs to sum 100%
        $this->assertEquals(100, $firstStage['percentage'] + $secondStage['percentage']);
    }

    /**
     * Test getting custom stages for a process with stages and return custom aggregation
     */
    public function testGetStagesPerProcessWithCustomAggregation()
    {
        $stages = [
            ['id' => 101, 'order' => 1, 'name' => 'Custom Stage 1', 'selected' => false],
            ['id' => 102, 'order' => 2, 'name' => 'Custom Stage 2', 'selected' => false],
        ];

        $process = Process::factory()->create([
            'name' => 'Test Process Custom Stages',
            'status' => 'ACTIVE',
            'stages' => json_encode($stages),
            'aggregation' => 'var_amount',
        ]);
        // Create 2 requests without stage
        ProcessRequest::factory()->count(2)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'data' => ['var_amount' => '10'],
        ]);
        // Create 5 requests in "Custom Stage 1" stage
        ProcessRequest::factory()->count(5)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'last_stage_id' => $stages[0]['id'],
            'last_stage_name' => 'Custom Stage 1',
            'data' => ['var_amount' => '10'],
        ]);
        // Create 3 requests in "Custom Stage 2" stage
        ProcessRequest::factory()->count(3)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'last_stage_id' => $stages[1]['id'],
            'last_stage_name' => 'Custom Stage 2',
            'data' => ['var_amount' => '10'],
        ]);

        $response = $this->apiCall('GET', route('api.processes.stages', ['process' => $process->id]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'stage_id',
                    'stage_name',
                    'percentage',
                    'percentage_format',
                    'agregation_sum',
                    'agregation_count',
                ],
            ],
        ]);

        // Verify custom stages
        $data = $response->json('data');
        $this->assertCount(2, $data);
        // Verify "1" stage
        $firstStage = collect($data)->firstWhere('stage_id', $stages[0]['id']);
        $this->assertEquals('Custom Stage 1', $firstStage['stage_name']);
        $this->assertEquals(50, $firstStage['agregation_sum']);
        $this->assertEquals(5, $firstStage['agregation_count']);

        // Verify "2" stage
        $secondStage = collect($data)->firstWhere('stage_id', $stages[1]['id']);
        $this->assertEquals('Custom Stage 2', $secondStage['stage_name']);
        $this->assertEquals(30, $secondStage['agregation_sum']);
        $this->assertEquals(3, $secondStage['agregation_count']);

        // Verify the percentage needs to sum 100%
        $this->assertEquals(100, $firstStage['percentage'] + $secondStage['percentage']);
    }

    /**
     * Test getting metrics for a process
     */
    public function testGetMetricsPerProcess()
    {
        $process = Process::factory()->create([
            'name' => 'Test Process Metrics',
            'status' => 'ACTIVE',
        ]);

        $response = $this->apiCall('GET', route('api.processes.metrics', ['process' => $process->id]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'metric_description',
                    'metric_count',
                    'metric_count_description',
                    'metric_value',
                    'metric_value_unit',
                ],
            ],
        ]);

        $data = $response->json('data');

        // Verify metrics array structure
        $this->assertCount(3, $data);
    }
}
