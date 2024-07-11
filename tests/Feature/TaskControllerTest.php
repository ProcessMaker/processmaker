<?php

namespace Tests\Feature;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RequestHelper;
    use ProcessTestingTrait;

    public function testShowScreen()
    {
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../Fixtures/rollback_test.bpmn')
        );
        $process = Process::orderBy('id', 'desc')->first();

        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);
        
        $instance = ProcessRequest::first();
        $task = ProcessRequestToken::where('element_name', 'Terminate Task')->first();

        dd($task);
        $processAbeRequest = ProcessAbeRequestToken::factory()->create();

        $response = $this->webCall('GET', 'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes');

        // check the correct view is called
        $response->assertViewIs('processes.screens.completedScreen');
        $response->assertStatus(200);
    }
}
