<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskAssignmentExecutionTest extends TestCase
{

    use RequestHelper;

    /**
     * @var Process $process
     */
    protected $process;

    /**
     * @var \ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface $task
     */
    protected $task;

    /**
     * @var \ProcessMaker\Models\User $assigned
     */
    protected $assigned;

    /**
     * @var array $requestStructure
     */
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
     * Create new task assignment type user successfully
     */
    private function loadTestProcessUserAssignment()
    {
        // Create a new process
        $this->process = factory(Process::class)->create();

        // Load a single task process
        $this->process->bpmn = file_get_contents(__DIR__ . '/processes/SingleTask.bpmn');

        // Create user to be assigned to the task
        $task_uid = 'UserTaskUID';
        $this->task = $this->process->getDefinitions()->getActivity($task_uid);
        $this->assigned = factory(User::class)->create([
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
        $user = factory(User::class)->create();

        $run = function($data) {
            $process = factory(Process::class)->create([
                'bpmn' => file_get_contents(__DIR__ . '/processes/ByUserIdAssignment.bpmn')
            ]);

            $route = route('api.process_events.trigger',
                [$process->id, 'event' => 'node_1']);
            return $this->apiCall('POST', $route, $data)->json();
        };

        $response = $run(['userIdInData' => $user->id]);
        $requestId = $response['id'];

        $task = ProcessRequestToken::where([
            'process_request_id' => $requestId,
            'status' => 'ACTIVE'
        ])->firstOrFail();

        $this->assertEquals($user->id, $task->user_id);

        // Assert it throws exception when the variable is missing
        $response = $run(['foo' => $user->id]);
        $this->assertEquals(
            $response['message'],
            'The variable, {{ userIdInData }}, which equals "", is not a valid User ID in the system'
        );

        // Assert it throws exception when the variable is not a valid user id
        $response = $run(['userIdInData' => 'foo']);
        $this->assertEquals(
            $response['message'],
            'The variable, {{ userIdInData }}, which equals "foo", is not a valid User ID in the system'
        );
    }
}
