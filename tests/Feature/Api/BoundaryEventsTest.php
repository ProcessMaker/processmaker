<?php

namespace Tests\Feature\Api;

use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use ProcessMaker\Models\ScheduledTask;

class BoundaryEventsTest extends TestCase
{
    use RequestHelper;
    use ProcessTestingTrait;

    /**
     * Tests the a process with a signal boundary event
     */
    public function testSignalBoundaryEvent()
    {
        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Signal_BoundaryEvent.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_2');

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')
            ->get();

        // "Task 1" and "Task 2" should be active
        $this->assertCount(2, $activeTokens);
        $this->assertEquals('Task 1', $activeTokens[0]->element_name);
        $this->assertEquals('Task 2', $activeTokens[1]->element_name);

        // Complete task 2, then a signal is triggered
        $this->completeTask($activeTokens[1]);

        // BoundaryEvent catches signal and pass the token to "Task 3"
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')
            ->get();
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 3', $activeTokens[0]->element_name);
    }

    /**
     * Tests a process with a timer boundary event
     */
    public function testCycleTimerBoundaryEvent()
    {
        // Mock current date for TaskSchedulerManager
        $now = TaskSchedulerManager::fakeToday('2018-05-01T00:00:00Z');

        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Timer_BoundaryEvent_Cycle.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_2');

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // "Task 1" must be active
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 1', $activeTokens[0]->element_name);

        // One timer should be scheduled
        $this->assertEquals(1, ScheduledTask::count());

        // Trigger timer event
        $now->modify('+1 minute');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        // There should be no scheduled tasks
        $this->assertEquals(0, ScheduledTask::count());

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // "Task 2" must be active
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 2', $activeTokens[0]->element_name);
    }

    /**
     * Tests a process with a error boundary event in a script task
     */
    public function testErrorBoundaryEventScriptTask()
    {
        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Error_BoundaryEvent_ScriptTask.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_2');

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // Since "Task 1" failed, BoundaryEvent catch the error and continue to "Task 2"
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 2', $activeTokens[0]->element_name);

        // Complete "Task 2"
        $this->completeTask($activeTokens[0]);

        // Check if the process was completed
        $instance->refresh();
        $this->assertEquals('COMPLETED', $instance->status);
    }

    /**
     * Tests a process with a error boundary event in a CallActivity
     *
     */
    public function testErrorBoundaryEventCallActivity()
    {
        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Error_EndEvent_CallActivity.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_4');
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')
            ->get();

        // The sub process have thrown an ErrorEvent that is catch by Boundary Event
        // and continue to Task 2
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 2', $activeTokens[0]->element_name);
    }

    /**
     * Tests a process with a cycle timer boundary event attached to a CallActivity
     *
     */
    public function testCycleTimerBoundaryEventCallActivity()
    {
        // Mock current date for TaskSchedulerManager
        $now = TaskSchedulerManager::fakeToday('2018-05-01T00:00:00Z');

        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Timer_BoundaryEvent_CallActivity.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_4');

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // "Call Activity" must be active in the main instance
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Call Activity', $activeTokens[0]->element_name);

        // Get active tokens in sub process
        $subInstance = ProcessRequest::orderBy('id', 'desc')->first();
        $activeTokensSub = $subInstance->tokens()->where('status', 'ACTIVE')->get();

        // "Sub Task" must be active in the sub process instance
        $this->assertCount(1, $activeTokensSub);
        $this->assertEquals('Sub Task', $activeTokensSub[0]->element_name);

        // Trigger timer event
        $now->modify('+1 minute');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // "Task 2" must be active
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 2', $activeTokens[0]->element_name);

        // SubProcess instance must be COMPLETED
        $subInstance->refresh();
        $this->assertEquals('COMPLETED', $subInstance->status);
    }

    /**
     * Tests the a process with a signal boundary event attached to a CallActivity
     */
    public function testSignalBoundaryEventCallActivity()
    {
        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Signal_BoundaryEvent_CallActivity.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_4');
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')
            ->get();

        // The sub process trigger a Signal that is catch by Boundary Event
        // and continue to Task 2, closing the CallActivity
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 2', $activeTokens[0]->element_name);
        $subInstance = ProcessRequest::orderBy('id', 'desc')->first();
        $this->assertEquals('COMPLETED', $subInstance->status);
    }

    /**
     * Tests a process with a cycle timer boundary non interrupting event attached to a task
     */
    public function testCycleTimerBoundaryEventNonInterrupting()
    {
        // Mock current date for TaskSchedulerManager
        $now = TaskSchedulerManager::fakeToday('2018-05-01T00:00:00Z');

        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Timer_BoundaryEvent_Cycle_NonInterrupting.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_2');

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // "Task 1" must be active
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 1', $activeTokens[0]->element_name);

        // Trigger timer event
        $now->modify('+1 minute');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // "Task 1" and "Task 2" must be active
        $this->assertCount(2, $activeTokens);
        $this->assertEquals('Task 1', $activeTokens[0]->element_name);
        $this->assertEquals('Task 2', $activeTokens[1]->element_name);
    }

    /**
     * Tests a process with an error boundary non interrupting event attached to a script task
     */
    public function testErrorBoundaryEventScriptTaskNonInterrupting()
    {
        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Error_BoundaryEvent_ScriptTask_NonInterrupting.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_2');

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // Since "Task 1" failed, BoundaryEvent catch the error and activate "Task 2"
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 2', $activeTokens[0]->element_name);

        // And keeps "Task 1" in FAILING status
        $failingTokens = $instance->tokens()->where('status', 'FAILING')->get();
        $this->assertCount(1, $failingTokens);
        $this->assertEquals('Task 1', $failingTokens[0]->element_name);
    }

    /**
     * Tests a process with an error boundary non interrupting event attached to a CallActivity
     */
    public function testErrorBoundaryEventCallActivityNonInterrupting()
    {
        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Error_BoundaryEvent_CallActivity_NonInterrupting.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_4');

        // The sub process have thrown an ErrorEvent that is catch by Boundary Event
        // which activates Task 2 and keeps the CallActivity in FAILING status
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 2', $activeTokens[0]->element_name);

        $failingTokens = $instance->tokens()->where('status', 'FAILING')->get();
        $this->assertCount(1, $failingTokens);
        $this->assertEquals('Call Activity', $failingTokens[0]->element_name);
    }

    /**
     * Tests a process with a cycle timer boundary non interrupting event attached to a CallActivity
     */
    public function testCycleTimerBoundaryEventCallActivityNonInterrupting()
    {
        // Mock current date for TaskSchedulerManager
        $now = TaskSchedulerManager::fakeToday('2018-05-01T00:00:00Z');

        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Timer_BoundaryEvent_CallActivity_NonInterrupting.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_4');

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // "Call Activity" must be active in the main instance
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Call Activity', $activeTokens[0]->element_name);

        // Get active tokens in sub process
        $subInstance = ProcessRequest::orderBy('id', 'desc')->first();
        $activeTokensSub = $subInstance->tokens()->where('status', 'ACTIVE')->get();

        // "Sub Task" must be active in the sub process instance
        $this->assertCount(1, $activeTokensSub);
        $this->assertEquals('Sub Task', $activeTokensSub[0]->element_name);

        // Trigger timer event
        $now->modify('+1 minute');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // "Call Activity" and "Task 2" must be active
        $this->assertCount(2, $activeTokens);
        $this->assertEquals('Call Activity', $activeTokens[0]->element_name);
        $this->assertEquals('Task 2', $activeTokens[1]->element_name);

        // SubProcess instance must keep ACTIVE
        $subInstance->refresh();
        $this->assertEquals('ACTIVE', $subInstance->status);
    }

    /**
     * Tests the a process with a signal boundary non interrupting event attached to a CallActivity
     */
    public function testSignalBoundaryEventCallActivityNonInterrupting()
    {
        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Signal_BoundaryEvent_CallActivity_NonInterrupting.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_4');
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')
            ->get();

        // The sub process trigger a Signal that is catch by Boundary Event
        // which activates Task 2 and keeps the CallActivity activated
        $this->assertCount(2, $activeTokens);
        $this->assertEquals('Call Activity', $activeTokens[0]->element_name);
        $this->assertEquals('Task 2', $activeTokens[1]->element_name);
    }
}
