<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ScheduledTask;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 * @group timer_events
 */
class IntermediateTimerEventTest extends TestCase
{
    use ResourceAssertionsTrait;
    use WithFaker;
    use RequestHelper;
    use ProcessTestingTrait;

    /**
     * @var Process
     */
    protected $process;

    private $requestStructure = [
        'id',
        'process_id',
        'user_id',
        'status',
        'name',
        'initiated_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestProcess(array $data = [])
    {
        $data['bpmn'] = Process::getProcessTemplate('IntermediateTimerEvent.bpmn');
        $process = Process::factory()->create($data);

        return $process;
    }

    public function testRegisterIntermediateTimerEvents()
    {
        $this->process = $this->createTestProcess();
        $this->be($this->user);
        ScheduledTask::get()->each->delete();

        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('IntermediateTimerEvent.bpmn');
        $process = Process::factory()->create($data);
        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('_2');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);

        // Complete first task to active the intermediate timer events.
        $token = $request->tokens()->where('element_id', '_3')->first();
        $this->completeTask($token);

        // at this point the save method should have created 4 rows in the
        // scheduled tasks table
        $tasks = ScheduledTask::all();
        $this->assertCount(4, $tasks->toArray());
    }

    public function testScheduleIntermediateTimerEvent()
    {
        $this->process = $this->createTestProcess();
        $this->be($this->user);
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('IntermediateTimerEvent.bpmn');
        $process = Process::factory()->create($data);
        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('_2');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);
        $task1 = $request->tokens()->where('element_id', '_3')->first();
        WorkflowManager::completeTask($process, $request, $task1, []);
        $manager = new TaskSchedulerManager();
        $task = new ScheduledTask();
        $task->process_id = $process->id;
        $task->process_request_id = $request->id;
        $task->configuration = '{"type":"TimeCycle","interval":"R4\/2019-02-13T13:08:00Z\/PT1M", "element_id" : "_5"}';
        $task->type = 'INTERMEDIATE_TIMER_EVENT';

        Bus::fake();
        $manager->executeIntermediateTimerEvent($task, json_decode($task->configuration));

        //Assert that the job was dispatched
        Bus::assertDispatched(\ProcessMaker\Jobs\CatchEvent::class);
    }

    /**
     * Tests a process with concurrent non interrupting boundary events attached to a CallActivity
     */
    public function testConnectedTimerEvents()
    {
        // Mock current date for TaskSchedulerManager
        $now = TaskSchedulerManager::fakeToday('2018-10-01T00:00:00Z');

        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/TimerEvents_Intermediate_Start.bpmn'));

        $now->modify('+1 day');
        //$now->modify('+1 minute');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        // One process request is started by the timer event
        $this->assertEquals(1, ProcessRequest::count());
        $instance = ProcessRequest::first();

        // Two timers are scheduled: timer start and first intermediate timer event
        $this->assertEquals(2, ScheduledTask::count());

        // "every day 8:00 UTC" must be active
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('every day 8:00 UTC', $activeTokens[0]->element_name);

        // Increase 4 hours, nothing must change
        $now->modify('+4 hour');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('every day 8:00 UTC', $activeTokens[0]->element_name);

        // Trigger first intermediate timer event
        $now->modify('+4 hour');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        // "every day 8:30 -4:00" must be active
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('every day 8:30 -4:00', $activeTokens[0]->element_name);

        // Increase 4:30 hours, "every day 8:30 -4:00" is reached (12:30 UTC)
        $now->modify('+4 hour');
        $now->modify('+30 minute');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        // "wait 4 hours" must be active (16:00)
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();
        $this->assertCount(1, $activeTokens);
        $this->assertEquals('wait 4 hours', $activeTokens[0]->element_name);

        // Increase 4 hours
        $now->modify('+4 hour');
        TaskSchedulerManager::fakeToday($now);
        $this->runScheduledTasks();

        // Process is completed
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();
        $this->assertCount(0, $activeTokens);
        $this->assertEquals(1, ProcessRequest::count());
        $this->assertEquals('COMPLETED', ProcessRequest::first()->status);
    }

    public function testScheduleIntermediateTimerEventWithMustacheSyntax()
    {
        $this->be($this->user);
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('IntermediateTimerEventMustache.bpmn');
        $process = Process::factory()->create($data);
        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('_2');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, ['interval' => 'PT8M']);
        $task1 = $request->tokens()->where('element_id', '_3')->first();
        WorkflowManager::completeTask($process, $request, $task1, []); // moves to timer event I guess

        // Time travel 5 minutes into the future
        Carbon::setTestNow(Carbon::now()->addMinute(5));

        // Re-schedule events for artisan call
        $scheduleManager = new TaskSchedulerManager();
        $scheduleManager->scheduleTasks();
        \Artisan::call('schedule:run');

        $iteToken = $request->tokens()->where('element_id', '_5')->firstOrFail();
        $this->assertEquals('ACTIVE', $iteToken->status); // Not enough time has passed

        // Time travel 5 more minutes into the future
        Carbon::setTestNow(Carbon::now()->addMinute(5));

        // Re-schedule events for artisan call
        $scheduleManager->scheduleTasks();
        \Artisan::call('schedule:run');

        $iteToken->refresh();
        $this->assertEquals('CLOSED', $iteToken->status);
    }
}
