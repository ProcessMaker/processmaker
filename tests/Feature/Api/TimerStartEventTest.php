<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ScheduledTask;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 * @group timer_events
 */
class TimerStartEventTest extends TestCase
{
    use ResourceAssertionsTrait;
    use WithFaker;
    use RequestHelper;

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
     * Initialize the controller tests
     */
    protected function withUserSetup()
    {
        $this->process = $this->createTestProcess();
    }

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestProcess(array $data = [])
    {
        $data['bpmn'] = Process::getProcessTemplate('TimerStartEvent.bpmn');
        $process = factory(Process::class)->create($data);

        return $process;
    }

    public function executeTimerStartEvent()
    {
        $this->process->getDefinitions();
    }

    public function testRegisterTimerEvents()
    {
        ScheduledTask::get()->each->delete();

        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('TimerStartEvent.bpmn');
        $process = factory(Process::class)->create($data);

        // at this point the save method should have created 4 rows in the
        // scheduled tasks table

        $tasks = ScheduledTask::all();
        $this->assertCount(4, $tasks->toArray());
    }

    /**
     * Tests that the next date of a interval is calculated correctly
     */
    public function testNextDateNayraInterval()
    {
        $manager = new TaskSchedulerManager();

        $cases = [
            [
                'lastExecution' => '2019-02-18T00:00:00Z',
                'currentDate' => '2019-02-19T00:00:00Z',
                'interval' => 'R/2019-02-15T00:00:00Z/P1D/2019-02-20T00:00:00Z',
                'type' => 'TimeCycle',
                'expectedNextDate' => '2019-02-19T00:00:00Z',
                'title' => '1 day recurrence, currentDate < endDate',
            ],
            [
                'lastExecution' => '2019-02-20T00:00:00Z',
                'currentDate' => '2019-02-21T00:00:00Z',
                'interval' => 'R/2019-02-15T00:00:00Z/P1D/2019-02-20T00:00:00Z',
                'type' => 'TimeCycle',
                'expectedNextDate' => null,
                'title' => '1 day recurrence, currentDate > endDate',
            ],
            [
                'lastExecution' => '2019-02-15T01:00:00Z',
                'currentDate' => '2019-02-15T10:00:00Z',
                'interval' => 'R4/2019-02-15T01:00:00Z/P1D',
                'type' => 'TimeCycle',
                'expectedNextDate' => '2019-02-16T01:00:00Z',
                'title' => '1 day recurrence currentDate > startDate',
            ],
            [
                'lastExecution' => '2019-02-19T01:00:00Z',
                'currentDate' => '2019-02-20T10:00:00Z',
                'interval' => 'R4/2019-02-15T01:00:00Z/P1D',
                'type' => 'TimeCycle',
                'expectedNextDate' => null,
                'title' => '1 day recurrence currentDate has passed the number of recurrences ',
            ],
            [
                'lastExecution' => null,
                'currentDate' => '2019-02-11T00:00:00Z',
                'interval' => 'R4/2019-02-15T00:00:00Z/P1M',
                'type' => 'TimeCycle',
                'expectedNextDate' => '2019-02-15T00:00:00Z',
                'title' => '1 day recurrence currentDate < startDate ',
            ],
            [
                'lastExecution' => '2019-04-15T00:00:00Z',
                'currentDate' => '2019-05-11T00:00:00Z',
                'interval' => 'R/2019-02-15T00:00:00Z/P1M',
                'type' => 'TimeCycle',
                'expectedNextDate' => '2019-05-15T00:00:00Z',
                'title' => '1 month recurrence currentDate < startDate ',
            ],
            [
                'lastExecution' => null,
                'currentDate' => '2019-02-14T02:01:00Z',
                'interval' => 'R/2019-02-14T11:02:00Z/PT1M',
                'type' => 'TimeCycle',
                'expectedNextDate' => '2019-02-14T11:02:00Z',
                'title' => '1 month recurrence currentDate < startDate by hours',
            ],
            [
                'lastExecution' => '2019-02-15T01:00:00Z',
                'currentDate' => '2019-02-20T10:00:00Z',
                'interval' => '2019-02-15T01:00:00Z',
                'type' => 'TimeDate',
                'expectedNextDate' => null,
                'title' => 'Specific date off',
            ],
            [
                'lastExecution' => null,
                'currentDate' => '2019-02-15T00:59:00Z',
                'interval' => '2019-02-15T01:00:00Z',
                'type' => 'TimeDate',
                'expectedNextDate' => '2019-02-15T01:00:00Z',
                'title' => 'Specific date on time',
            ],
            [
                'lastExecution' => null,
                'currentDate' => '2019-02-15T01:01:00Z',
                'interval' => 'P1D',
                'type' => 'TimeDuration',
                'expectedNextDate' => '2019-02-16T01:01:00Z',
                'title' => 'Time Duration of 1 day',
            ],
        ];

        foreach ($cases as $case) {
            $currentDate = new \DateTime($case['currentDate']);
            $nayraInterval = $case['interval'];
            $expectedNextDate = $case['expectedNextDate'] === null ? null : new \DateTime($case['expectedNextDate']);
            $lastExecution = isset($case['lastExecution']) ? new \DateTime($case['lastExecution']) : null;
            $nextDate = $manager->nextDate($currentDate, (object) [
                'type' => $case['type'],
                'interval' => $nayraInterval,
                'element_id' => 'test',
            ], $lastExecution);
            $this->assertEquals($expectedNextDate, $nextDate, 'Assertion failed in "' . $case['title'] . '""');
        }
    }

    public function testScheduleStartEvent()
    {
        //Check triggerStartEvent should be called when process is ACTIVE
        WorkflowManager::shouldReceive('triggerStartEvent')
            ->once()
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::any());

        //Prepare process data from template
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('TimerStartEvent.bpmn');

        //Create process
        $process = factory(Process::class)->create($data);

        $manager = new TaskSchedulerManager();
        $task = new ScheduledTask();
        $task->process_id = $process->id;
        $task->configuration = '{"type":"TimeCycle","interval":"R4\/2019-02-13T13:08:00Z\/PT1M", "element_id" : "_9"}';
        $task->type = 'TIMER_START_EVENT';
        $manager->executeTimerStartEvent($task, json_decode($task->configuration));
    }

    public function testScheduleMustNotStartTimerEventWhenProcessInactive()
    {
        //Check triggerStartEvent should NEVER be called when process is INACTIVE
        WorkflowManager::shouldReceive('triggerStartEvent')
            ->never()
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::any());

        //Prepare process data from template
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('TimerStartEvent.bpmn');

        //Set status to INACTIVE
        $data['status'] = 'INACTIVE';

        //Create process
        $process = factory(Process::class)->create($data);

        $manager = new TaskSchedulerManager();
        $task = new ScheduledTask();
        $task->process_id = $process->id;
        $task->configuration = '{"type":"TimeCycle","interval":"R4\/2019-02-13T13:08:00Z\/PT1M", "element_id" : "_9"}';
        $task->type = 'TIMER_START_EVENT';
        $manager->executeTimerStartEvent($task, json_decode($task->configuration));
    }
}
