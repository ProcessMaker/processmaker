<?php

namespace Tests\Feature;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessHtmlTest extends TestCase
{
    use RequestHelper;

    protected static $DO_NOT_SEND = 'DO_NOT_SEND';

    /**
     * A process with html entities in the documentation field should be able to be loaded.
     * By default, the bpmn processes are loaded with the html entities support.
     */
    public function test_process_with_html_can_be_loaded()
    {
        $this->user = User::factory()->create([
            'is_administrator' => false,
        ]);
        $process = $this->createProcessFromBPMN('tests/Fixtures/process_with_html.bpmn');

        $definitions = $process->getDefinitions(true);

        $this->assertNotEmpty($definitions, 'The process could not be loaded correctly');
    }

    /**
     * A process with html entities in the documentation field should be able to be stored.
     */
    public function test_store_process_with_html_entities()
    {
        $route = route('api.processes.store');
        $base = Process::factory()->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        // Add a bpmn content
        $bpmn = file_get_contents(base_path('tests/Fixtures/process_with_html.bpmn'));
        $array['bpmn'] = $bpmn;
        $response = $this->apiCall('POST', $route, $array);
        $response->assertStatus(201);
        $data = $response->json();
        $process = Process::where('id', $data['id'])->first();

        // Fix bpmn content to remove the html entities
        $fixedBpmn = BpmnDocument::replaceHtmlEntities($process->bpmn);
        $this->assertEquals($fixedBpmn, $process->bpmn);
    }
    
    /**
     * A process with html entities in the documentation field should be able to be updated.
     */
    public function test_update_process_with_html_entities()
    {
        // First create a process
        $route = route('api.processes.store');
        $base = Process::factory()->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        $response = $this->apiCall('POST', $route, $array);
        $response->assertStatus(201);
        $data = $response->json();
        $process = Process::where('id', $data['id'])->first();
        
        // Now update the process
        $bpmn = file_get_contents(base_path('tests/Fixtures/process_with_html.bpmn'));
        $updateRoute = route('api.processes.update', ['process' => $process->id]);
        $updateData = [
            'name' => $process->name . ' Updated',
            'description' => $process->description . ' Updated',
            'bpmn' => $bpmn,
        ];
        $updateResponse = $this->apiCall('PUT', $updateRoute, $updateData);
        $updateResponse->assertStatus(200);
        
        // Reload the process from database
        $updatedProcess = Process::where('id', $process->id)->first();
        
        // Check if the process was updated correctly
        $this->assertEquals($process->name . ' Updated', $updatedProcess->name);
        
        // Fix bpmn content to remove the html entities
        $fixedBpmn = BpmnDocument::replaceHtmlEntities($updatedProcess->bpmn);
        $this->assertEquals($fixedBpmn, $updatedProcess->bpmn);
    }

    /**
     * Test updating BPMN content directly via the updateBpmn endpoint.
     * This endpoint allows updating only the BPMN content, not the other process attributes.
     */
    public function test_update_bpmn_endpoint_with_html_entities()
    {
        // First create a process
        $route = route('api.processes.store');
        $base = Process::factory()->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        $response = $this->apiCall('POST', $route, $array);
        $response->assertStatus(201);
        $data = $response->json();
        $process = Process::where('id', $data['id'])->first();
        
        // Now update the process
        $bpmn = file_get_contents(base_path('tests/Fixtures/process_with_html.bpmn'));
        $updateRoute = route('api.processes.update_bpmn', ['process' => $process->id]);
        $updateData = [
            'name' => $process->name . ' Updated',
            'description' => $process->description . ' Updated',
            'bpmn' => $bpmn,
        ];
        $updateResponse = $this->apiCall('PUT', $updateRoute, $updateData);
        $updateResponse->assertStatus(200);
        
        // Reload the process from database
        $updatedProcess = Process::where('id', $process->id)->first();
        
        // Check if the process was updated correctly
        $this->assertEquals($process->name . ' Updated', $updatedProcess->name);
        
        // Fix bpmn content to remove the html entities
        $fixedBpmn = BpmnDocument::replaceHtmlEntities($updatedProcess->bpmn);
        $this->assertEquals($fixedBpmn, $updatedProcess->bpmn);
    }
}
