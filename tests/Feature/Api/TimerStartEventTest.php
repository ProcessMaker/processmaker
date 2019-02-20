<?php
namespace Tests\Feature\Api;

use Faker\Provider\DateTime;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Managers\WorkflowEventManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class TimerStartEventTest extends TestCase
{

    use ResourceAssertionsTrait;
    use WithFaker;
    use RequestHelper;

    /**
     * @var Process $process
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
        'updated_at'
    ];

    /**
     * Initialize the controller tests
     *
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
                'currentDate' => '2019-02-15T10:00:00Z',
                'interval' => 'R4/2019-02-15T01:00:00Z/P1D',
                'expectedNextDate' => '2019-02-16T01:00:00Z'
            ],
            [
                'currentDate' => '2019-02-20T10:00:00Z',
                'interval' => 'R4/2019-02-15T01:00:00Z/P1D',
                'expectedNextDate' => null
            ],
            [
                'currentDate' => '2019-02-20T10:00:00Z',
                'interval' => '2019-02-15T01:00:00Z',
                'expectedNextDate' => null
            ],
//            [
//                'currentDate' => '2019-02-11T00:00:00Z',
//                'interval' => 'R4/2019-02-15T00:00:00Z/P1M',
//                'expectedNextDate' => '2019-02-15T00:00:00Z'
//            ],
//            [
//                'currentDate' => '2019-05-11T00:00:00Z',
//                'interval' => 'R4/2019-02-15T00:00:00Z/P1M',
//                'expectedNextDate' => '2019-05-15T00:00:00Z'
//            ],
//            [
//                'currentDate' => '2019-05-11T00:00:00Z',
//                'interval' => 'R/2019-02-15T00:00:00Z/P1M',
//                'expectedNextDate' => '2019-05-15T00:00:00Z'
//            ],
//            [
//                'currentDate' => '2019-02-14T02:01:00Z',
//                'interval' => 'R/2019-02-14T11:02:00Z/PT1M',
//                'expectedNextDate' => '2019-02-14T11:02:00Z'
//            ],
        ];

        foreach($cases as $case) {
            $currentDate = new \DateTime($case['currentDate']);
            $nayraInterval = $case['interval'];
            $expectedNextDate = $case['expectedNextDate'] === null ? null : new \DateTime($case['expectedNextDate']);
            $nextDate = $manager->nextDate($currentDate, $nayraInterval);
            $this->assertEquals($expectedNextDate, $nextDate);
        }
    }

    public function testScheduleStartEvent()
    {
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('TimerStartEvent.bpmn');
        $process = factory(Process::class)->create($data);

        $manager = new TaskSchedulerManager();
        $task = new ScheduledTask();
        $task->process_id = $process->id;
        $task->configuration = '{"type":"TimeCycle","interval":"R4\/2019-02-13T13:08:00Z\/PT1M", "element_id" : "_9"}';
        $task->type= 'TIMER_START_EVENT';
        $manager->executeTimerStartEvent($task, json_decode($task->configuration));

        // If no exception has been thrown, this assertion will be executed
        $this->assertTrue(true);
    }
}
