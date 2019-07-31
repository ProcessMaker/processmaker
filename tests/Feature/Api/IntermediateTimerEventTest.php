<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ScheduledTask;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ProcessTestingTrait;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class IntermediateTimerEventTest extends TestCase
{

    use ResourceAssertionsTrait;
    use WithFaker;
    use RequestHelper;
    use ProcessTestingTrait;

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
        $data['bpmn'] = Process::getProcessTemplate('IntermediateTimerEvent.bpmn');
        $process = factory(Process::class)->create($data);
        return $process;
    }

    public function testRegisterIntermediateTimerEvents()
    {
        ScheduledTask::get()->each->delete();

        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('IntermediateTimerEvent.bpmn');
        $process = factory(Process::class)->create($data);
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
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('IntermediateTimerEvent.bpmn');
        $process = factory(Process::class)->create($data);
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
        $task->type= 'INTERMEDIATE_TIMER_EVENT';
        $manager->executeIntermediateTimerEvent($task, json_decode($task->configuration));

        // executing a second time
        $task->configuration = '{"type":"TimeCycle","interval":"R4\/2019-02-13T13:08:00Z\/PT1M", "element_id" : "_5"}';
        $manager->executeIntermediateTimerEvent($task, json_decode($task->configuration));

        // If no exception has been thrown, this assertion will be executed
        $this->assertTrue(true);
    }
}
