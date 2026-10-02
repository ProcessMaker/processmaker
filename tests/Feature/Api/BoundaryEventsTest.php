<?php

namespace Tests\Feature\Api;

use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

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
     * @group timer_events
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
     * @group timer_events
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
     *
     * @group timer_events
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
     *
     * @group timer_events
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

    /**
     * Tests a process with concurrent non interrupting boundary events attached to a CallActivity
     *
     * @group timer_events
     */
    public function testConcurrentBoundaryEventCallActivityNonInterrupting()
    {
        // Mock current date for TaskSchedulerManager
        $now = TaskSchedulerManager::fakeToday('2018-05-01T00:00:00Z');

        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Concurrent_BoundaryEvent_CallActivity_NonInterrupting.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, '_4');

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // "Task 1" must be active
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Call Activity', $activeTokens[0]->element_name);

        // Trigger timer event
        $now->modify('+4 minute');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();

        // "Call Activity", "Task 2", "Task 3", "Task 4" must be active
        $this->assertCount(4, $activeTokens);
        $this->assertEquals('Call Activity', $activeTokens[0]->element_name);
        $this->assertEquals('Task 3', $activeTokens[1]->element_name);
        $this->assertEquals('Task 4', $activeTokens[2]->element_name);
        $this->assertEquals('Task 2', $activeTokens[3]->element_name);

        // "Task 5" must be active in the sub process
        $subInstance = ProcessRequest::orderBy('id', 'desc')->first();
        $activeTokensSub = $subInstance->tokens()->where('status', 'ACTIVE')->get();
        $this->assertCount(1, $activeTokensSub);
        $this->assertEquals('Task 5', $activeTokensSub[0]->element_name);
    }

    /**
     * Tests the a process with a timer boundary event on a multi-instance task
     */
    public function testTimerBoundaryEventMultiInstance()
    {
        $now = TaskSchedulerManager::fakeToday('2018-05-01T00:00:00Z');
        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/Timer_BoundaryEvent_MultiInstance.bpmn'));

        // Start a process instance
        $instance = $this->startProcess($process, 'node_1', [
            'input' => [
                ['abc' => 123],
                ['abc' => 456],
                ['abc' => 789],
            ],
        ]);

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')
            ->get();

        // "Task 1" should be active with 3 tokens
        $this->assertCount(3, $activeTokens);
        $this->assertEquals('Task 1', $activeTokens[0]->element_name);
        $this->assertEquals('Task 1', $activeTokens[1]->element_name);
        $this->assertEquals('Task 1', $activeTokens[2]->element_name);

        // Move forward 1 minute
        $now->modify('+1 minute');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        // BoundaryEvent catches timer then
        // all the active tokens of Task 1 were closed
        $activeTokens[0]->refresh();
        $this->assertEquals('CLOSED', $activeTokens[0]->status);
        $activeTokens[1]->refresh();
        $this->assertEquals('CLOSED', $activeTokens[0]->status);
        $activeTokens[2]->refresh();
        $this->assertEquals('CLOSED', $activeTokens[0]->status);

        // The active token goes to "Task 2"
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')
            ->get();
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('Task 2', $activeTokens[0]->element_name);
    }

    /**
     * Tests that an interrupting timer boundary event closes an unclaimed self-service task
     * and clears its self-service flag
     *
     * @group timer_events
     */
    public function testTimerBoundaryEventClosesSelfServiceTask()
    {
        $now = TaskSchedulerManager::fakeToday('2018-05-01T00:00:00Z');
        $process = $this->createSelfServiceTimerProcess(true);

        $instance = $this->startProcess($process, 'node_1');

        $selfServiceToken = $instance->tokens()->where('element_id', 'node_2')->firstOrFail();
        $this->assertEquals('ACTIVE', $selfServiceToken->status);
        $this->assertEquals(1, $selfServiceToken->is_self_service);
        $this->assertNull($selfServiceToken->user_id);

        $now->modify('+1 minute');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        $selfServiceToken->refresh();
        $this->assertEquals('CLOSED', $selfServiceToken->status);
        $this->assertEquals(0, $selfServiceToken->is_self_service);
        $this->assertEquals('COMPLETED', $instance->refresh()->status);

        $route = route('api.tasks.index', [
            'process_request_id' => $instance->id,
            'pmql' => 'is_self_service = 1',
        ]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
        $this->assertNotContains($selfServiceToken->id, array_column($response->json('data'), 'id'));
    }

    /**
     * Tests that a non-interrupting timer boundary event keeps the self-service task open
     *
     * @group timer_events
     */
    public function testNonInterruptingTimerBoundaryEventKeepsSelfServiceTaskOpen()
    {
        $now = TaskSchedulerManager::fakeToday('2018-05-01T00:00:00Z');
        $process = $this->createSelfServiceTimerProcess(false);

        $instance = $this->startProcess($process, 'node_1');

        $now->modify('+1 minute');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        $selfServiceToken = $instance->tokens()->where('element_id', 'node_2')->firstOrFail();
        $this->assertEquals('ACTIVE', $selfServiceToken->status);
        $this->assertEquals(1, $selfServiceToken->is_self_service);
        $this->assertNull($selfServiceToken->user_id);
    }

    private function createSelfServiceTimerProcess(bool $cancelActivity)
    {
        $selfServiceUser = User::factory()->create(['status' => 'ACTIVE']);
        $bpmn = file_get_contents(__DIR__ . '/processes/Timer_BoundaryEvent_SelfService.bpmn');
        $bpmn = str_replace('{selfServiceUserId}', $selfServiceUser->id, $bpmn);
        if (!$cancelActivity) {
            $bpmn = str_replace('cancelActivity="true"', 'cancelActivity="false"', $bpmn);
        }

        return $this->createProcess($bpmn);
    }
}
