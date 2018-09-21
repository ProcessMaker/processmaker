<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\TestCase;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class ProcessExecutionTest extends TestCase
{

    use DatabaseTransactions;
    use WithFaker;

    /**
     *
     * @var User $user 
     */
    protected $user;

    /**
     * @var Process $process
     */
    protected $process;
    private $requestStructure = [
        'uuid',
        'process_uuid',
        'user_uuid',
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
    protected function setUp()
    {
        parent::setUp();
        //Login as an valid user
        $this->user = factory(User::class)->create();
        $this->actingAs($this->user, 'api');
        $this->process = $this->createTestProcess();
    }

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestProcess()
    {
        $process = factory(Process::class)->create([
            'bpmn' => Process::getProcessTemplate('SingleTask.bpmn')
        ]);
        //Assign the task to $this->user
        $taskUuid = 'UserTaskUID';
        factory(ProcessTaskAssignment::class)->create([
            'process_uuid' => $process->uuid,
            'process_task_uuid' => $taskUuid,
            'assignment_uuid' => $this->user->uuid,
            'assignment_type' => 'user',
        ]);
        return $process;
    }

    /**
     * Execute a process
     */
    public function testExecuteAProcess()
    {
        //Start a process request
        $route = route('process_events.trigger', [$this->process->uuid_text, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->json('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Complete the task
        $route = route('tasks.update', [$tasks[0]['uuid'], 'status' => 'COMPLETED']);
        $response = $this->json('PUT', $route, $data);
        $task = $response->json();
        //Check the task is closed
        $this->assertEquals('CLOSED', $task['status']);
        $this->assertNotNull($task['completed_at']);
        //Check the request is completed
        $this->assertEquals('COMPLETED', $task['process_request']['status']);
        $this->assertNotNull($task['process_request']['completed_at']);
    }

    /**
     * Test to get the list of available start events of the process.
     */
    public function testGetListOfStartEvents()
    {
        $route = route('processes.show', [$this->process->uuid_text, 'include'=>'events']);
        $response = $this->json('GET', $route);
        //Check the inclusion of events
        $response->assertJsonStructure(['events'=>[['id','name']]]);
    }

    /**
     * Test to start a process without sending an event identifier.
     */
    public function testStartProcessEmptyEventId()
    {
        $route = route('process_events.trigger', [$this->process->uuid_text]);
        $data = [];
        $response = $this->json('POST', $route, $data);
        $response->assertStatus(404);
    }

    /**
     * Test to start a process without sending an non-existent event.
     */
    public function testStartProcessWithNonExistingEventId()
    {
        $route = route('process_events.trigger', [$this->process->uuid_text, 'event' => 'non-existent']);
        $data = [];
        $response = $this->json('POST', $route, $data);
        $response->assertStatus(404);
    }

    /**
     * Try to close an already closed task
     */
    public function testCloseAClosedTask()
    {
        //Start a process request
        $route = route('process_events.trigger', [$this->process->uuid_text, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->json('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Complete the task
        $route = route('tasks.update', [$tasks[0]['uuid'], 'status' => 'COMPLETED']);
        $response = $this->json('PUT', $route, $data);
        $task = $response->json();
        //Check the task is closed
        $this->assertEquals('CLOSED', $task['status']);
        $this->assertNotNull($task['completed_at']);
        //Try to complete the task again
        $route = route('tasks.update', [$tasks[0]['uuid'], 'status' => 'COMPLETED']);
        $response = $this->json('PUT', $route, $data);
        $task = $response->json();
        $response->assertStatus(422);
    }

    /**
     * Try to update a task status
     */
    public function testUpdateTaskInvalidStatus()
    {
        //Start a process request
        $route = route('process_events.trigger', [$this->process->uuid_text, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->json('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Update to a FAILING status
        $route = route('tasks.update', [$tasks[0]['uuid'], 'status' => 'FAILING']);
        $response = $this->json('PUT', $route, $data);
        $response->assertStatus(422);
        //Update to a *invalid* status
        $route = route('tasks.update', [$tasks[0]['uuid'], 'status' => '*invalid*']);
        $response = $this->json('PUT', $route, $data);
        $response->assertStatus(422);
    }
}
