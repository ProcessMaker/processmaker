<?php

namespace Tests\Feature\Api;

use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\CatchSignalEventProcess;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Jobs\StartEvent;
use ProcessMaker\Jobs\ThrowSignalEvent;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TerminatedEndEventTest extends TestCase
{
    use RequestHelper;

    public function testTerminateEndEventClosesAllTokens()
    {
        // Create the process to test
        ImportProcess::dispatchNow(
            file_get_contents(__DIR__.'/../Fixtures/terminate_end_event.json')
        );

        $process = Process::orderBy('id', 'desc')->first();

        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);
        // Verify that 3 tasks are created
        $this->assertEquals(3, ProcessRequestToken::where('element_type', 'task')->count());

        // Find the task that goes to the Terminate End Event and execute it
        $instance = ProcessRequest::first();
        $task = ProcessRequestToken::where('element_name', 'Terminate Task')->first();
        WorkflowManager::completeTask($process, $instance, $task, []);

        // Verify that all tasks are closed and the request completed
        $this->assertEquals(3, ProcessRequestToken::where('element_type', 'task')
            ->where('status', 'CLOSED')
            ->count());
        $instance->refresh();
        $this->assertEquals('COMPLETED', $instance->status);
    }

    public function testEndEventDoesNotClosePendingTasks()
    {
        // Create the process to test
        ImportProcess::dispatchNow(
            file_get_contents(__DIR__.'/../Fixtures/terminate_end_event.json')
        );

        $process = Process::orderBy('id', 'desc')->first();

        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);
        // Verify that 3 tasks are created
        $this->assertEquals(3, ProcessRequestToken::where('element_type', 'task')->count());

        // Find the task whose flow goes to a normal end event
        $instance = ProcessRequest::first();
        $task = ProcessRequestToken::where('element_name', 'Task 1')->first();
        WorkflowManager::completeTask($process, $instance, $task, []);

        // Verify that just one task is closed and it is Task 1
        $this->assertEquals(1, ProcessRequestToken::where('element_type', 'task')
            ->where('status', 'CLOSED')
            ->count());
        $task->refresh();
        $this->assertEquals('CLOSED', $task->status);
    }
}
