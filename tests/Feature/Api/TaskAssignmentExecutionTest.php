<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use ProcessMaker\Exception\ThereIsNoProcessManagerAssignedException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskAssignmentExecutionTest extends TestCase
{
    use RequestHelper;

    /**
     * @var Process
     */
    protected $process;

    /**
     * @var \ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface
     */
    protected $task;

    /**
     * @var \ProcessMaker\Models\User
     */
    protected $assigned;

    /**
     * @var array
     */
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
     * Create new task assignment type user successfully
     */
    private function loadTestProcessUserAssignment()
    {
        // Create a new process
        $this->process = Process::factory()->create();

        // Load a single task process
        $this->process->bpmn = file_get_contents(__DIR__ . '/processes/SingleTask.bpmn');

        // Create user to be assigned to the task
        $task_uid = 'UserTaskUID';
        $this->task = $this->process->getDefinitions()->getActivity($task_uid);
        $this->assigned = User::factory()->create([
            'id' => $this->task->getProperty('assignedUsers'),
            'status' => 'ACTIVE',
        ]);

        // When save the process creates the assignments
        $this->process->save();
    }

    /**
     * Execute a process with single user assignment
     */
    public function testSingleUserAssignment()
    {
        $this->loadTestProcessUserAssignment();

        //Start a process request
        $route = route('api.process_events.trigger',
            [$this->process->id, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);

        //Verify status
        $response->assertStatus(201);

        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();

        $requestId = $request['id'];

        $request = ProcessRequest::find($requestId);

        //Token 0: user of event start
        $this->assertEquals($request->tokens[0]->user_id, $this->user->id);

        //Token 1: user of task
        $this->assertEquals($request->tokens[1]->user_id, $this->assigned->id);
    }

    public function testUserByIdAssignment()
    {
        $user = User::factory()->create();

        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/processes/ByUserIdAssignment.bpmn'),
        ]);

        $run = function ($data) use ($process) {
            $route = route('api.process_events.trigger',
                [$process->id, 'event' => 'node_1']);

            return $this->apiCall('POST', $route, $data)->json();
        };

        $response = $run(['userIdInData' => $user->id]);
        $requestId = $response['id'];

        $task = ProcessRequestToken::where([
            'process_request_id' => $requestId,
            'status' => 'ACTIVE',
        ])->firstOrFail();

        $this->assertEquals($user->id, $task->user_id);

        // Assert it throws exception when the process does not have a process manager
        $response = $run(['foo' => $user->id]);
        $this->assertEquals(
            $response['errors'][0]['message'],
            'Task cannot be assigned since there is no Process Manager associated to the process.'
        );

        // Assert it does not fail when the variable is not a valid user id and a process manager is assigned
        $process->manager_id = $user->id;
        $process->save();
        $response = $run(['userIdInData' => 'foo']);
        $this->assertFalse(array_key_exists('errors', $response));
    }

    public function testDueDate()
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/processes/TaskConfiguredCustomDueIn.bpmn'),
        ]);
        $data = [
            'var_due_date' => 24
        ];
        $route = route('api.process_events.trigger',
            [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, $data);
        // Verify status
        $response->assertStatus(201);
        // Get the ProcessRequest
        $currentDate = Carbon::now();
        $task = ProcessRequestToken::where([
            'process_request_id' => $response['id'],
            'status' => 'ACTIVE',
        ])->firstOrFail();

        $this->assertGreaterThanOrEqual($task->due_at, $currentDate);
    }

    public function testSelfServeAssignment()
    {
        $users = User::factory()->count(20)->create(['status'=>'ACTIVE']);
        $userWithNoGroup = User::factory()->create(['status'=>'ACTIVE']);
        $unassignedUser = User::factory()->create(['status'=>'ACTIVE']);

        $group = Group::factory()->create();
        foreach ($users as $user) {
            GroupMember::factory()->create([
                'member_id' => $user->id,
                'member_type' => User::class,
                'group_id' => $group->id,
            ]);
        }

        $screen = Screen::factory()->create();

        $bpmn = file_get_contents(__DIR__ . '/processes/SelfServeAssignment.bpmn');
        $bpmn = str_replace(
            ['[SCREEN_ID]', '[GROUP_ID]', '[USER_ID]'],
            [$screen->id, $group->id, $userWithNoGroup->id],
            $bpmn
        );
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
            'user_id' => $this->user->id,
        ]);

        $event = $process->getDefinitions()->getEvent('node_4');
        $processRequest = WorkflowManager::triggerStartEvent($process, $event, []);
        $task = $processRequest->refresh()->tokens()->where('status', 'ACTIVE')->first();

        $updateTaskUrl = route('api.tasks.update', [$task->id]);

        // Assert someone not individually assigned or assigned to a group can not take the task
        $this->user = $unassignedUser;
        $listTasksUrl = route('api.tasks.index', ['pmql' => "user_id = {$unassignedUser->id}"]);
        $response = $this->apiCall('GET', $listTasksUrl)->json();
        $this->assertCount(0, $response['data']);
        $response = $this->apiCall('put', $updateTaskUrl, [
            'is_self_service' => false,
            'user_id' => $this->user->id,
        ]);
        $response->assertStatus(403); // should be not authorized

        // Assert a group member can claim the task
        $this->user = $users[1];
        $listTasksUrl = route('api.tasks.index');
        $response = $this->apiCall('GET', $listTasksUrl)->json();

        $this->assertCount(1, $response['data']);
        $this->assertEquals($response['data'][0]['id'], $task->id);
        $response = $this->apiCall('put', $updateTaskUrl, [
            'is_self_service' => false,
            'user_id' => $this->user->id,
        ]);
        $response->assertStatus(200);

        // Reset task assignment
        $task = $processRequest->refresh()->tokens()->where('status', 'ACTIVE')->first();
        $task['is_self_service'] = true;
        $task['user_id'] = null;
        $task->save();

        // Assert assigned individual user can claim the task
        $this->user = $userWithNoGroup;
        $listTasksUrl = route('api.tasks.index');
        $response = $this->apiCall('GET', $listTasksUrl)->json();

        $this->assertCount(1, $response['data']);
        $this->assertEquals($response['data'][0]['id'], $task->id);
        $response = $this->apiCall('put', $updateTaskUrl, [
            'is_self_service' => false,
            'user_id' => $this->user->id,
        ]);
        $response->assertStatus(200);
    }

    public function testSelfServeUserPersistence()
    {
        $users = User::factory()->count(20)->create(['status'=>'ACTIVE']);
        $userWithNoGroup = User::factory()->create(['status'=>'ACTIVE']);
        $unassignedUser = User::factory()->create(['status'=>'ACTIVE']);

        $group = Group::factory()->create();
        foreach ($users as $user) {
            GroupMember::factory()->create([
                'member_id' => $user->id,
                'member_type' => User::class,
                'group_id' => $group->id,
            ]);
        }

        $screen = Screen::factory()->create();

        $bpmn = file_get_contents(__DIR__ . '/processes/SelfServeAssignment.bpmn');
        $bpmn = str_replace(
            ['[SCREEN_ID]', '[GROUP_ID]', '[USER_ID]'],
            [$screen->id, $group->id, $userWithNoGroup->id],
            $bpmn
        );
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
            'user_id' => $this->user->id,
        ]);

        $event = $process->getDefinitions()->getEvent('node_4');
        $processRequest = WorkflowManager::triggerStartEvent($process, $event, []);
        $task = $processRequest->refresh()->tokens()->where('status', 'ACTIVE')->first();

        $updateTaskUrl = route('api.tasks.update', [$task->id]);

        // Assert that the user magic variable is empty
        $this->assertNull($processRequest->data['_user']);

        // Assert a group member can claim the task
        $this->user = $users[1];
        $response = $this->apiCall('put', $updateTaskUrl, [
            'is_self_service' => false,
            'user_id' => $this->user->id,
        ]);
        $response->assertStatus(200);

        // Reset task assignment
        $task = $processRequest->refresh()->tokens()->where('status', 'ACTIVE')->first();
        $task['is_self_service'] = true;
        $task['user_id'] = null;
        $task->save();

        // Assert individual assigned user can claim the task
        $this->user = $userWithNoGroup;
        $response = $this->apiCall('put', $updateTaskUrl, [
            'is_self_service' => false,
            'user_id' => $this->user->id,
        ]);
        $response->assertStatus(200);

        // Assert that the user magic variable is now populated
        $processRequest->refresh();
        $this->assertIsArray($processRequest->data['_user']);
        $this->assertArrayHasKey('id', $processRequest->data['_user']);
        $this->assertEquals($this->user->id, $processRequest->data['_user']['id']);
    }

    /**
     * Execute a process with Process Manager assignment
     */
    public function testProcessManagerAssignment()
    {
        $manager = User::factory()->create(['status'=>'ACTIVE']);

        // Create a new process
        $this->process = Process::factory()->create();

        // Load process with single task assigned to Process Manager
        $this->process->bpmn = file_get_contents(__DIR__ . '/processes/AssignedToProcessManager.bpmn');
        $this->process->manager_id = $manager->id;
        $this->process->save();

        //Start a process request
        $route = route('api.process_events.trigger',
            [$this->process->id, 'event' => 'node_1']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);

        //Verify status
        $response->assertStatus(201);

        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();

        $requestId = $request['id'];

        $request = ProcessRequest::find($requestId);

        //Token 0: user of event start
        $this->assertEquals($this->user->id, $request->tokens[0]->user_id);

        //Token 1: user of task should be Process Manager
        $this->assertEquals($manager->id, $request->tokens[1]->user_id);
    }

    /**
     * Execute a process with Process Manager assignment, but without a Manager defined
     */
    public function testProcessManagerAssignmentWithoutAManagerAssociated()
    {
        // Create a new process
        $this->process = Process::factory()->create();

        // Load process with single task assigned to Process Manager
        $this->process->bpmn = file_get_contents(__DIR__ . '/processes/AssignedToProcessManager.bpmn');
        $this->process->save();

        //Start a process request
        $route = route('api.process_events.trigger',
            [$this->process->id, 'event' => 'node_1']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);

        //Verify status
        $response->assertStatus(201);

        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();

        $requestId = $request['id'];

        $request = ProcessRequest::find($requestId);

        //Token 0: user of event start
        $this->assertEquals($this->user->id, $request->tokens[0]->user_id);

        //Token 1: user of task should be null
        $this->assertEquals(null, $request->tokens[1]->user_id);

        //Error message should be set
        $task = $request->tokens[1]->getDefinition(true);
        $error = new ThereIsNoProcessManagerAssignedException($task);
        $this->assertEquals($error->getMessage(), $request->errors[0]['message']);
        $this->assertEquals($task->getId(), $request->errors[0]['element_id']);
    }
}
