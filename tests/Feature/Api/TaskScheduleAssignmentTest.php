<?php

namespace Tests\Feature\Api;

use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskScheduleAssignmentTest extends TestCase
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
     * @var \ProcessMaker\Models\User $testUser
     */
    protected $testUser;

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
    private function loadTestProcess()
    {
        // Create a new process
        $this->process = factory(Process::class)->create();

        // Load a single task process
        $this->process->bpmn = file_get_contents(__DIR__ . '/processes/SingleTask.bpmn');

        // Create user to be assigned to the task
        $task_uid = 'UserTaskUID';
        $this->task = $this->process->getDefinitions()->getActivity($task_uid);
        $this->testUser = factory(User::class)->create([
            'id' => $this->task->getProperty('assignedUsers'),
            'status' => 'SCHEDULE',
        ]);

        // When save the process creates the assignments
        $this->process->save();
    }

    /**
     * Execute a process with single user assignment
     */
    public function testSingleUserAssignment()
    {
        $this->loadTestProcess();

        // Start a process request
        $route = route('api.process_events.trigger',
            [$this->process->id, 'event' => 'StartEventUID']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);

        // Verify status
        $response->assertStatus(201);

        // Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();

        $requestId = $request['id'];

        $request = ProcessRequest::find($requestId);

        // Token 0: user of event start
        $this->assertEquals($request->tokens[0]->user_id, $this->user->id);

        // Token 1: user of task
        $this->assertEquals($request->tokens[1]->user_id, $this->testUser->id);

        // Complete task
        $this->completeTask($request->tokens[1]);

        // Reload request
        $request = ProcessRequest::find($requestId);
        
        // Verify assigned user is the same of the previous task
        $this->assertEquals($request->tokens[1]->user_id, $request->tokens[2]->user_id);
    }

    /**
     * Complete task
     *
     * @param \ProcessMaker\Models\ProcessRequestToken $task
     * @param array $data
     *
     * @return \ProcessMaker\Models\ProcessRequestToken
     */
    private function completeTask(ProcessRequestToken $task, $data = [])
    {
        //Call the manager to trigger the start event
        $process = $task->process;
        $instance = $task->processRequest;
        WorkflowManager::completeTask($process, $instance, $task, $data);
        return $task->refresh();
    }
}
