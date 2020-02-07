<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\ProcessRequest;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class ProcessExecutionTest extends TestCase
{
    use RequestHelper;
    use WithFaker;

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
    protected function withUserSetUp()
    {
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
        $taskId = 'UserTaskUID';
        factory(ProcessTaskAssignment::class)->create([
            'process_id' => $process->id,
            'process_task_id' => $taskId,
            'assignment_id' => $this->user->id,
            'assignment_type' => User::class,
        ]);
        return $process;
    }

    /**
     * Execute a process
     */
    public function testExecuteAProcess()
    {
        //Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'StartEventUID']);
        $data = ['foo' => 'bar'];
        $response = $this->apiCall('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $tasks = $response->json('data');

        // Verify that the start event data was stored in the task
        $task = ProcessRequestToken::where([
            'process_request_id' => $request['id'],
            'status' => 'TRIGGERED'
        ])->first();
        $this->assertEquals(['foo'=>'bar'], $task->data);

        //Complete the task
        $route = route('api.tasks.update', [$tasks[0]['id'], 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $task = $response->json();
        //Check the task is closed
        $this->assertEquals('CLOSED', $task['status']);
        $this->assertNotNull($task['completed_at']);
        //Check the request is completed
        $this->assertEquals('COMPLETED', $task['process_request']['status']);
        $this->assertNotNull($task['process_request']['completed_at']);

        // Check that a log comment was created
        $response = $this->apiCall('GET', '/comments', [
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $tasks[0]['process_request_id'],
        ]);
        $message = $response->json()['data'][0]['body'];
        $this->assertEquals(
            $this->user->fullname . " has completed the task " . $task['element_name'],
            $message
        );

    }

    /**
     * Test to get the list of available start events of the process.
     */
    public function testGetListOfStartEvents()
    {
        $route = route('api.processes.show', [$this->process->id, 'include' => 'events']);
        $response = $this->apiCall('GET', $route);
        //Check the inclusion of events
        $response->assertJsonStructure(['events' => [['id', 'name']]]);
    }

    /**
     * Test to start a process without sending an event identifier.
     */
    public function testStartProcessEmptyEventId()
    {
        $route = route('api.process_events.trigger', [$this->process->id]);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        $response->assertStatus(404);
    }

    /**
     * Test to start a process without sending an non-existent event.
     */
    public function testStartProcessWithNonExistingEventId()
    {
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'non-existent']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        $response->assertStatus(404);
    }

    /**
     * Try to close an already closed task
     */
    public function testCloseAClosedTask()
    {
        //Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $tasks = $response->json('data');
        //Complete the task
        $route = route('api.tasks.update', [$tasks[0]['id'], 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $task = $response->json();
        //Check the task is closed
        $this->assertEquals('CLOSED', $task['status']);
        $this->assertNotNull($task['completed_at']);
        //Try to complete the task again
        $route = route('api.tasks.update', [$tasks[0]['id'], 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $task = $response->json();
        $response->assertStatus(422);
    }

    /**
     * Try to update a task status
     */
    public function testUpdateTaskInvalidStatus()
    {
        //Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $tasks = $response->json('data');
        //Update to a FAILING status
        $route = route('api.tasks.update', [$tasks[0]['id'], 'status' => 'FAILING']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $response->assertStatus(422);
        //Update to a *invalid* status
        $route = route('api.tasks.update', [$tasks[0]['id'], 'status' => '*invalid*']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $response->assertStatus(422);
    }

    /**
     * Get the list of processes to start with categories and start events.
     */
    public function testGetListProcessesToStart()
    {
        $this->actingAs($this->user);
        //Create two additional processes
        $this->createTestProcess();
        $uncProcess = $this->createTestProcess(['process_category_id' => null]);
        //Get the start event id
        $uncProcessEvents = $uncProcess->events;
        //Get the list of processes (with and without category) and its start events
        $route = route('api.processes.index', ['include' => 'events,category', 'order_by' => 'name']);
        $response = $this->apiCall('GET', $route);
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
        $this->withoutExceptionHandling();
        //Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $tasks = $response->json('data');
        //Get the task information
        $route = route('api.tasks.show', [$tasks[0]['id'], 'include' => 'definition']);
        $response = $this->apiCall('GET', $route, $data);
        $response->assertJsonStructure([
            'id',
            'status',
            'created_at',
            'element_id',
            'element_name',
            'definition' => [
                'id',
                'name'
            ]
        ]);
    }

    /**
     * Test that the task gets assigned to the correct person in a group
     */
    public function testTaskAssignedToGroup()
    {
        $foo = factory(User::class)->create(
            ['firstname' => 'Foo', 'status' => 'ACTIVE']
        );
        $bar = factory(User::class)->create(
            ['firstname' => 'Bar', 'status' => 'ACTIVE']
        );
        $group = factory(Group::class)->create(
            ['id' => 999, 'status' => 'ACTIVE']
        );
        
        foreach([$foo, $bar] as $user) {
            factory(GroupMember::class)->create([
                'member_id' => $user->id,
                'member_type' => User::class,
                'group_id' => $group->id
            ]);
        }

        $group_process = factory(Process::class)->create(['status' => 'ACTIVE']);
        $data['bpmn'] = Process::getProcessTemplate('SingleTaskAssignedToGroup.bpmn');
        $group_process->update($data);

        $taskId = 'node_3';
        factory(ProcessTaskAssignment::class)->create([
            'process_id' => $group_process->id,
            'process_task_id' => $taskId,
            'assignment_id' => $group->id,
            'assignment_type' => Group::class
        ]);

        //Start a process request
        $route = route('api.process_events.trigger', [$group_process->id, 'event' => 'node_2']);
        $response = $this->apiCall('POST', $route, []);

        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->actingAs($foo, 'api')->json('GET', $route);
        $tasks = $response->json('data');

        // Assert the first user "foo" got the task
        $this->assertEquals(count($tasks), 1);
        $task_id = $tasks[0]['id'];
        
        //Get the active tasks of the request for the other user
        $route = route('api.tasks.index');
        $response = $this->actingAs($bar, 'api')->json('GET', $route);
        $tasks = $response->json('data');
        
        // Assert that "bar" did NOT get the task
        $this->assertEquals(count($tasks), 0);

        // Complete the task
        $route = route('api.tasks.update', [$task_id, 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);

        // Start another request
        $route = route('api.process_events.trigger', [$group_process->id, 'event' => 'node_2']);
        $response = $this->apiCall('POST', $route, []);

        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->actingAs($bar, 'api')->json('GET', $route);
        $tasks = $response->json('data');

        // Assert the next user "bar" got the task
        $this->assertEquals(count($tasks), 1);
        
        // Complete the task
        $route = route('api.tasks.update', [$tasks[0]['id'], 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        
        // Start another request
        $route = route('api.process_events.trigger', [$group_process->id, 'event' => 'node_2']);
        $response = $this->apiCall('POST', $route, []);

        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->actingAs($foo, 'api')->json('GET', $route);
        $tasks = $response->json('data');

        // Assert the next user, "foo" again, got the task
        $this->assertEquals($tasks[0]['advanceStatus'], 'completed');
        $this->assertEquals($tasks[1]['advanceStatus'], 'open');
    }
}
