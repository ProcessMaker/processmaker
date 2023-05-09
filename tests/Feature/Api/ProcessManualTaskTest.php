<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Test the execution of manual tasks
 *
 * @group process_tests
 */
class ProcessManualTaskTest extends TestCase
{
    use RequestHelper;
    use WithFaker;

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
        $data['bpmn'] = file_get_contents(__DIR__ . '/processes/ManualTask.bpmn');
        $process = Process::factory()->create($data);
        //Assign the task to $this->user
        $taskId = 'TaskUID';
        ProcessTaskAssignment::factory()->create([
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
            $this->user->fullname . ' has completed the task ' . $task['element_name'],
            $message
        );
    }

    /**
     * Try to close an already closed manual task
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
                'name',
            ],
        ]);
    }

    /**
     * Test that the task gets assigned to the correct person in a group
     */
    public function testTaskAssignedToGroup()
    {
        $foo = User::factory()->create(
            ['firstname' => 'Foo', 'status' => 'ACTIVE']
        );
        $bar = User::factory()->create(
            ['firstname' => 'Bar', 'status' => 'ACTIVE']
        );
        $group = Group::factory()->create(
            ['id' => 999, 'status' => 'ACTIVE']
        );

        foreach ([$foo, $bar] as $user) {
            GroupMember::factory()->create([
                'member_id' => $user->id,
                'member_type' => User::class,
                'group_id' => $group->id,
            ]);
        }

        $group_process = Process::factory()->create(['status' => 'ACTIVE']);
        $data['bpmn'] = Process::getProcessTemplate('SingleTaskAssignedToGroup.bpmn');
        $group_process->update($data);

        $taskId = 'node_3';
        ProcessTaskAssignment::factory()->create([
            'process_id' => $group_process->id,
            'process_task_id' => $taskId,
            'assignment_id' => $group->id,
            'assignment_type' => Group::class,
        ]);

        //Start a process request
        $route = route('api.process_events.trigger', [$group_process->id, 'event' => 'node_2']);
        $response = $this->apiCall('POST', $route, []);

        //Get the active tasks of the request
        $route = route('api.tasks.index', ['user_id' => $foo->id]);
        $response = $this->actingAs($foo, 'api')->json('GET', $route);
        $tasks = $response->json('data');

        // Assert the first user "foo" got the task
        $this->assertEquals(1, count($tasks));
        $task_id = $tasks[0]['id'];

        //Get the active tasks of the request for the other user
        //Since PR #3470, user_id is required as parameter
        $route = route('api.tasks.index', ['user_id' => $bar->id]);
        $response = $this->actingAs($bar, 'api')->json('GET', $route);
        $tasks = $response->json('data');

        // Assert that "bar" did NOT get the task
        $this->assertEquals(0, count($tasks));

        // Complete the task
        $route = route('api.tasks.update', [$task_id, 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);

        // Start another request
        $route = route('api.process_events.trigger', [$group_process->id, 'event' => 'node_2']);
        $response = $this->apiCall('POST', $route, []);

        //Get the active tasks of the request
        //Since PR #3470, user_id is required as parameter
        $route = route('api.tasks.index', ['user_id' => $bar->id]);
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
        $route = route('api.tasks.index', ['user_id' => $foo->id]);
        $response = $this->actingAs($foo, 'api')->json('GET', $route);
        $tasks = $response->json('data');

        // Assert the next user, "foo" again, got the task
        $this->assertEquals('completed', $tasks[0]['advanceStatus']);
        $this->assertEquals('open', $tasks[1]['advanceStatus']);
    }
}
