<?php

namespace Tests\Feature\Api;

use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskLockAssignmentTest extends TestCase
{
    use RequestHelper;

    /**
     * @var \ProcessMaker\Models\User $user1
     */
    protected $user1;

    /**
     * @var \ProcessMaker\Models\User $user2
     */
    protected $user2;

    /**
     * Create new task assignment type user successfully
     * @param string $processFileName
     * @throws \Throwable
     */
    private function loadProcess($processFileName)
    {
        // Create a new process
        $this->process = factory(Process::class)->create();

        // Load a single task process
        $this->process->bpmn = file_get_contents(__DIR__ . '/processes/' . $processFileName);

        $this->process->save();

        // Create a group and 2 users to assign to group
        $this->user1 = factory(User::class)->create(['status' => 'ACTIVE']);
        $this->user2 = factory(User::class)->create(['status' => 'ACTIVE']);

        // Group with id 100 is created and the 2 users are attached to it
        $group = factory(Group::class)->create(['id'=>100, 'status' => 'ACTIVE']);

        $group_member = new GroupMember();
        $group_member->group()->associate($group);
        $group_member->member()->associate($this->user1);
        $group_member->saveOrFail();

        $group_member = new GroupMember();
        $group_member->group()->associate($group);
        $group_member->member()->associate($this->user2);
        $group_member->saveOrFail();
    }

    /**
     * Validates that when the assignmentLock attribute is set to true, when the flow returns to the task
     * the user that was assigned to the task the first time, is assigned again.
     */
    public function testWithLockAssignment()
    {
        $this->loadProcess('LockAssignment.bpmn');

        // Start a process request
        $route = route('api.process_events.trigger',
            [$this->process->id, 'event' => 'start_event']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);

        // Verify status
        $response->assertStatus(201);

        $requestFromResponse = $response->json();

        $requestId = $requestFromResponse['id'];
        $request = ProcessRequest::find($requestId);

        // Token 0: user of event start
        $this->assertEquals($request->tokens[0]->user_id, $this->user->id);

        // Token 1: user of task
        $this->assertEquals($request->tokens[1]->user_id, $this->user1->id);

        // Complete task 1
        $this->completeTask($request->tokens[1], ['age' => 5]);

        // Reload request
        $request = ProcessRequest::find($requestId);

        // For the round robin algorithm the assigned user to task 2 should be user1
        $this->assertEquals($this->user1->id, $request->tokens[2]->user_id);

        // Complete task 2,
        $this->completeTask($request->tokens[2], ['age' => 5]);
        $request = ProcessRequest::find($requestId);

        // Task 1 should be assigned to the user 1 instead of the user 2 for the assignmentLock attribute
        $this->assertEquals($this->user1->id, $request->tokens[3]->user_id);
    }

    /**
     *  Tests that when a task has the assignmentLock attribute set to false, the normal rules
     *  of assignment are exectued.
     *
     * @throws \Throwable
     */
    public function testWithNoLockAssignment()
    {
        $this->loadProcess('NoLockAssignment.bpmn');

        // Start a process request
        $route = route('api.process_events.trigger',
            [$this->process->id, 'event' => 'start_event']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);

        // Verify status
        $response->assertStatus(201);

        $requestFromResponse = $response->json();

        $requestId = $requestFromResponse['id'];
        $request = ProcessRequest::find($requestId);

        // Token 0: user of event start
        $this->assertEquals($request->tokens[0]->user_id, $this->user->id);

        // Token 1: user of task
        $this->assertEquals($request->tokens[1]->user_id, $this->user1->id);

        // Complete task 1
        $this->completeTask($request->tokens[1], ['age' => 5]);

        // Reload request
        $request = ProcessRequest::find($requestId);

        // For the round robin algorithm the assigned user to task 2 should be user1
        $this->assertEquals($this->user1->id, $request->tokens[2]->user_id);

        // Complete task 2,
        $this->completeTask($request->tokens[2], ['age' => 5]);
        $request = ProcessRequest::find($requestId);

        // The round robin algorithm should assign  the user 2  to the task
        $this->assertEquals($this->user2->id, $request->tokens[3]->user_id);
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
