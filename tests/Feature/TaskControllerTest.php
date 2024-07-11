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
        // $bpmn = file_get_contents(__DIR__ . '/../../Fixtures/rollback_test.bpmn');
        // $bpmn = str_replace('[task_user_id]', $this->user->id, $bpmn);
        // $screen = Screen::factory()->create();
        // $processRequest = ProcessRequest::factory()->create([
        //     'process_id' => $process->getKey(),
        //     'bpmn' => $bpmn,
        // ]);

        // $process = Process::factory()->create();

        // $processRequestToken = ProcessRequestToken::factory()->create([
        //     'process_id' => $process->getKey(),
        //     'process_request_id' => $processRequest->getKey()
        // ]);

        // $processAbeRequest = ProcessAbeRequestToken::factory()->create();

        // ProcessTaskAssignment::factory()->create([
        //     'process_id' => $processAbeRequest->process_id,
        //     'process_task_id' => 'node_255',
        //     'assignment_id' => $this->user->id,
        //     'assignment_type' => 'ProcessMaker\Models\User',
        // ]);

        // $definitions = $process->getDefinitions();
        // $startEvent = $definitions->getEvent('node_1');
        // $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);
        $bpmn = file_get_contents(__DIR__ . '/../Fixtures/rollback_test.bpmn');
        $bpmn = str_replace('[task_user_id]', $this->user->id, $bpmn);
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
        ]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
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
