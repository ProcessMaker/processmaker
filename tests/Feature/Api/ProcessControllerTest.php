<?php

namespace ProcessMaker\Api\Tests\Feature;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
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
}
