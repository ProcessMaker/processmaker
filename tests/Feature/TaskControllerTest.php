<?php

namespace Tests\Feature;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RequestHelper;

    public function testShowScreen()
    {
        $processAbeRequest = ProcessAbeRequestToken::factory()->create();
        $process = Process::find($processAbeRequest->process_id);

        ProcessTaskAssignment::factory()->create([
            'process_id' => $processAbeRequest->process_id,
            'process_task_id' => 'node_255',
            'assignment_id' => $this->user->id,
            'assignment_type' => 'ProcessMaker\Models\User',
        ]);

        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('node_1');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);

        $response = $this->webCall('GET', 'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes');

        // check the correct view is called
        $response->assertViewIs('processes.screens.completedScreen');
        $response->assertStatus(200);
    }
}
