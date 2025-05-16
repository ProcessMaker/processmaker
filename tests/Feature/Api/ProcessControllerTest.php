<?php

namespace ProcessMaker\Api\Tests\Feature;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\User;
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
     *
     * @return void
     */
    public function testSaveAndRetrieveStages()
    {
        // Sample stages data
        $stagesData = [
            [
                'id' => 1,
                'name' => 'Request Send',
                'order' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Request Reviewed',
                'order' => 2,
            ],
            [
                'id' => 2,
                'name' => 'Manager Reviewed',
                'order' => 3,
            ],
        ];

        // Create a new process and save stages as JSON
        $process = Process::factory()->create([
            'name' => 'Sample Stage Process',
            'status' => 'ACTIVE',
            'stages' => json_encode($stagesData),
        ]);

        // Retrieve the process from the database
        $retrievedProcess = Process::find($process->id);

        // Assert that the stages are correctly retrieved
        $this->assertNotNull($retrievedProcess);
        $this->assertIsString($retrievedProcess->stages);
        $retrievedStages = json_decode($retrievedProcess->stages, true);

        // Assert the content of the stages
        $this->assertEquals($stagesData, $retrievedStages);
    }

    /**
     * Test that a process returns its stages correctly.
     *
     * This test creates a process with a predefined list of stages
     * and verifies that the GET endpoint `/processes/{process}/stages`
     * returns the correct JSON structure.
     *
     * @return void
     */
    public function test_can_get_process_stages()
    {
        $stages = [
            ['id' => 1, 'order' => 1, 'label' => 'Start', 'selected' => false],
            ['id' => 2, 'order' => 2, 'label' => 'Review', 'selected' => true],
        ];

        $process = Process::factory()->create([
            'stages' => $stages,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/1.0/processes/{$process->id}/stages");

        $response->assertOk()
            ->assertJson([
                'data' => $stages,
            ]);
    }

    /**
     * Test that stages can be saved for a process.
     *
     * This test sends a POST request to `/processes/{process}/stages`
     * with a set of stage data, and verifies that:
     * - The response contains the saved stages
     * - The process record in the database was updated correctly
     *
     * @return void
     */
    public function test_can_save_process_stages()
    {
        $process = Process::factory()->create();

        $newStages = [
            ['id' => 1, 'order' => 1, 'label' => 'New Stage 1', 'selected' => true],
            ['id' => 2, 'order' => 2, 'label' => 'New Stage 2', 'selected' => false],
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/1.0/processes/{$process->id}/stages", [
                'stages' => $newStages,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => $newStages,
            ]);

        $this->assertEquals($newStages, $process->fresh()->stages);
    }
}
