<?php
namespace Tests\Feature\Api;

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
    private function createTestProcess(array $data = [])
    {
        $data['bpmn'] = Process::getProcessTemplate('SingleTask.bpmn');
        $process = factory(Process::class)->create($data);
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
        $route = route('api.process_events.trigger', [$this->process->uuid_text, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->json('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Complete the task
        $route = route('api.tasks.update', [$tasks[0]['uuid'], 'status' => 'COMPLETED']);
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
        $route = route('api.processes.show', [$this->process->uuid_text, 'include' => 'events']);
        $response = $this->json('GET', $route);
        //Check the inclusion of events
        $response->assertJsonStructure(['events' => [['id', 'name']]]);
    }

    /**
     * Test to start a process without sending an event identifier.
     */
    public function testStartProcessEmptyEventId()
    {
        $route = route('api.process_events.trigger', [$this->process->uuid_text]);
        $data = [];
        $response = $this->json('POST', $route, $data);
        $response->assertStatus(404);
    }

    /**
     * Test to start a process without sending an non-existent event.
     */
    public function testStartProcessWithNonExistingEventId()
    {
        $route = route('api.process_events.trigger', [$this->process->uuid_text, 'event' => 'non-existent']);
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
        $route = route('api.process_events.trigger', [$this->process->uuid_text, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->json('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Complete the task
        $route = route('api.tasks.update', [$tasks[0]['uuid'], 'status' => 'COMPLETED']);
        $response = $this->json('PUT', $route, $data);
        $task = $response->json();
        //Check the task is closed
        $this->assertEquals('CLOSED', $task['status']);
        $this->assertNotNull($task['completed_at']);
        //Try to complete the task again
        $route = route('api.tasks.update', [$tasks[0]['uuid'], 'status' => 'COMPLETED']);
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
        $route = route('api.process_events.trigger', [$this->process->uuid_text, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->json('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Update to a FAILING status
        $route = route('api.tasks.update', [$tasks[0]['uuid'], 'status' => 'FAILING']);
        $response = $this->json('PUT', $route, $data);
        $response->assertStatus(422);
        //Update to a *invalid* status
        $route = route('api.tasks.update', [$tasks[0]['uuid'], 'status' => '*invalid*']);
        $response = $this->json('PUT', $route, $data);
        $response->assertStatus(422);
    }

    /**
     * Get the list of processes to start with categories and start events.
     */
    public function testGetListProcessesToStart()
    {
        //Create two additional processes
        $this->createTestProcess();
        $uncProcess = $this->createTestProcess(['process_category_uuid' => null]);
        //Get the start event id
        $uncProcessEvents = $uncProcess->events;
        //Get the list of processes (with and without category) and its start events
        $route = route('api.processes.index', ['include' => 'events,category', 'order_by' => 'name']);
        $response = $this->json('GET', $route);
        //Check the inclusion of events
        $response->assertJsonStructure(['data' => ['*' => ['events' => [['id', 'name']], 'category']]]);
        $data = $response->json('data');
        $list = ['Uncategorized' => []];
        foreach ($data as $process) {
            $categoryName = $process['category'] ? $process['category']['name'] : 'Uncategorized';
            $list[$categoryName][] = $process;
        }
        $this->assertArraySubset(
            [
            'Uncategorized' => [
                [
                    'name' => $uncProcess->name,
                    'events' => [
                        ['id' => $uncProcessEvents[0]['id']]
                    ]
                ]
            ]
            ], $list);
    }

    /**
     * Test to get the status information of a task
     */
    public function testGetTaskStatusPage()
    {
        //Start a process request
        $route = route('api.process_events.trigger', [$this->process->uuid_text, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->json('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Get the task information
        $route = route('api.tasks.show', [$tasks[0]['uuid'], 'include' => 'definition']);
        $response = $this->json('GET', $route, $data);
        $response->assertJsonStructure([
            'uuid',
            'status',
            'created_at',
            'element_uuid',
            'element_name',
            'definition' => [
                'id',
                'name'
            ]
        ]);
    }
}
