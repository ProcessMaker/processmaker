<?php

namespace Tests\Feature\Api;

use ProcessMaker\Exception\ThereIsNoProcessManagerAssignedException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskAssignmentByVariableTest extends TestCase
{
    use RequestHelper;

    public function testProcessVariableAssignmentWithSimpleIds()
    {
        // Create users of a group and a user without group
        $user = User::factory()->create(['status'=>'ACTIVE']);
        $group = $this->createGroup(5);
        $process = $this->createProcess('process_variable', 'usersVariable', 'groupsVariable', '', false);

        // The first assignment should be to user (is the first created user)
        $response = $this->startTestProcess($process, ['usersVariable' => $user->id, 'groupsVariable' => $group->id]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();
        $this->assertEquals($user->id, $task->user_id);

        // The second assignment should be to the first user of the created group
        $response = $this->startTestProcess($process, ['usersVariable' => $user->id, 'groupsVariable' => $group->id]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();
        $this->assertEquals($group->users->first()->id, $task->user_id);
    }

    public function testProcessVariableAssignmentWithArrayOfIds()
    {
        // Create users of a group and a user without group
        $users = User::factory(2)->create(['status'=>'ACTIVE']);
        $group1 = $this->createGroup(6);
        $group2 = $this->createGroup(5);
        $process = $this->createProcess('process_variable', 'usersVariable', 'groupsVariable', '', false);

        // The first assignment should be to user (is the first created user)
        $response = $this->startTestProcess($process, [
            'usersVariable' => $users->pluck('id')->toArray(),
            'groupsVariable' => [$group1->id, $group2->id],
        ]);

        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();
        $this->assertEquals($users->first()->id, $task->user_id);

        // The second assignment should be to the second user
        $response = $this->startTestProcess($process, [
            'usersVariable' => $users->pluck('id')->toArray(),
            'groupsVariable' => [$group1->id, $group2->id],
        ]);

        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();
        $this->assertEquals($users->get(1)->id, $task->user_id);

        // The third assignment should be to the first user of the first created group
        $response = $this->startTestProcess($process, [
            'usersVariable' => $users->pluck('id')->toArray(),
            'groupsVariable' => [$group1->id, $group2->id],
        ]);

        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();
        $this->assertEquals($group1->users->first()->id, $task->user_id);
    }

    public function testProcessVariableAssignmentWithInvalidUsers()
    {
        // Create users of a group and a user without group
        $users = User::factory(2)->create(['status'=>'INACTIVE']);
        $group1 = $this->createGroup(6, 'INACTIVE');
        $group2 = $this->createGroup(5, 'INACTIVE');
        $process = $this->createProcess('process_variable', 'usersVariable', 'groupsVariable', '', false);

        // The first assignment should be to user (is the first created user)
        $response = $this->startTestProcess($process, [
            'usersVariable' => $users->pluck('id')->toArray(),
            'groupsVariable' => [$group1->id, $group2->id],
        ]);

        $requestId = $response['id'];
        $assignedTasks = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])
            ->get()
            ->count();
        $this->assertEquals(0, $assignedTasks);
    }

    public function testSelfServiceWithProcessVariableAssignment()
    {
        $user = User::factory()->create(['status'=>'ACTIVE']);
        $group = $this->createGroup(5);
        $process = $this->createProcess('process_variable', 'usersVariable', 'groupsVariable', '', true);

        $response = $this->startTestProcess($process, ['usersVariable' => $user->id, 'groupsVariable' => [$group->id]]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();

        $this->assertEquals(1, $task->is_self_service);
        // As it is self service the user must be null
        $this->assertEquals(null, $task->user_id);
        // The column self_service_groups should have the configured groups and users
        $this->assertEquals(['users' => [$user->id], 'groups' =>[$group->id]], $task->self_service_groups);
    }

    public function testSelfServiceClaims()
    {
        // Create users of a group and a user without group
        $user = User::factory()->create(['status'=>'ACTIVE']);
        $userWithoutClaim = User::factory()->create(['status'=>'ACTIVE']);
        $group = $this->createGroup(5);
        $process = $this->createProcess('process_variable', 'usersVariable', 'groupsVariable', '', true);

        $response = $this->startTestProcess($process, ['usersVariable' => $user->id, 'groupsVariable' => [$group->id]]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();

        // Assert some users that can claim the task
        $updateTaskUrl = route('api.tasks.update', [$task->id]);
        $this->user = $user;
        $response = $this->apiCall('put', $updateTaskUrl, [
            'is_self_service' => false,
            'user_id' => $this->user->id,
        ]);
        $response->assertStatus(200);

        // Reset task assignment
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();
        $task['is_self_service'] = true;
        $task['user_id'] = null;
        $task->save();

        //with a user in the group
        $updateTaskUrl = route('api.tasks.update', [$task->id]);
        $this->user = $group->users->last();
        $response = $this->apiCall('put', $updateTaskUrl, [
            'is_self_service' => false,
            'user_id' => $this->user->id,
        ]);
        $response->assertStatus(200);

        // Reset task assignment
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();
        $task['is_self_service'] = true;
        $task['user_id'] = null;
        $task->save();

        // Assert that a user that is not in the assignment can't claim the task
        $this->user = $userWithoutClaim;
        $response = $this->apiCall('put', $updateTaskUrl, [
            'is_self_service' => false,
            'user_id' => $this->user->id,
        ]);
        $response->assertStatus(403);
    }

    public function testSelfServiceWithUserGroupAssignment()
    {
        // Create users of a group and a user without group
        $users = User::factory()->count(3)->create(['status'=>'ACTIVE']);
        $group = $this->createGroup(5);
        $process = $this->createProcess('user_group', implode(',', $users->pluck('id')->toArray()), $group->id, '', true);

        $response = $this->startTestProcess($process, []);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();

        $this->assertEquals(1, $task->is_self_service);
        // As it is self service the user must be null
        $this->assertEquals(null, $task->user_id);
        // The column self_service_groups should have the configured groups and users
        $this->assertEquals(['users' => $users->pluck('id')->toArray(), 'groups' =>[$group->id]], $task->self_service_groups);
    }

    public function testSelfServiceWithExpressionAssignment()
    {
        // Create users of a group and a user without group
        $users = User::factory()->count(5)->create(['status'=>'ACTIVE']);
        $group = $this->createGroup(5);
        $rules = [
            ['type' => 'user', 'assignee' => $users->get(0)->id, 'expression' => 'TestVar<10'],
            ['type' => 'user', 'assignee' => $users->get(1)->id, 'expression' => 'TestVar<10'],
            ['type' => 'user', 'assignee' => $users->get(2)->id, 'expression' => 'TestVar>10'],
            ['type' => 'group', 'assignee' => $group->id, 'expression' => 'TestVar<10'],
            ['type' => 'user', 'assignee' => $users->get(3)->id, 'expression' => null],
        ];
        $process = $this->createProcess('rule_expression', '', '', $rules, true);

        $response = $this->startTestProcess($process, ['TestVar' => 5]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();

        // Assert that the task has been stored correctly in the database
        $this->assertEquals(1, $task->is_self_service);
        $this->assertEquals(null, $task->user_id);
        $this->assertEquals(['users' => [$users->get(0)->id, $users->get(1)->id], 'groups' =>[$group->id]], $task->self_service_groups);

        // Now we'll test that the default assignment rule runs correctly
        $response = $this->startTestProcess($process, ['TestVar' => 10]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();

        // Assert that the task has been stored correctly in the database
        $this->assertEquals(1, $task->is_self_service);
        $this->assertEquals(null, $task->user_id);
        $this->assertEquals(['users' => [$users->get(3)->id], 'groups' =>[]], $task->self_service_groups);
    }

    // tests assignment using object values (FOUR-8708)
    public function testProcessVariableAssignmentWithObjectVariable()
    {
        // test with variables with simple ids
        $user = User::factory()->create(['status'=>'ACTIVE']);
        $group = $this->createGroup(5);
        $process = $this->createProcess('process_variable', 'obj.assign.usersVariable', 'obj.assign.groupsVariable', '', false);

        // the assignment should go to the user
        $dataObj = ['obj'=>['assign'=>['usersVariable'=>$user->id, 'groupsVariable'=>$group->id]]];
        $response = $this->startTestProcess($process, $dataObj);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();
        $this->assertEquals($user->id, $task->user_id);


        // test with variables with array of ids
        // Create users of a group and a user without group
        $users = User::factory(2)->create(['status'=>'ACTIVE']);
        $group1 = $this->createGroup(6);
        $group2 = $this->createGroup(5);
        $process = $this->createProcess('process_variable', 'obj.assign.usersVariable', 'obj.assign.groupsVariable', '', false);
        $dataObj = ['obj'=>['assign'=>['usersVariable'=>$users->pluck('id')->toArray(), 'groupsVariable'=>[$group1->id, $group2->id]]]];

        // The assignment should be to user (the first created user)
        $response = $this->startTestProcess($process, $dataObj);

        $requestId = $response['id'];
        $task = ProcessRequestToken::where(['process_request_id' => $requestId, 'status' => 'ACTIVE'])->firstOrFail();
        $this->assertEquals($users->first()->id, $task->user_id);
    }

    /**
     * Creates a process in which the assignment of the first task is configured based on the parameters of this function
     *
     * @param $assignment assignment type for the task
     * @param $assignedUsers variable that has the list of userIds
     * @param $assignedGroups variable that has the list of groupIds
     * @param $rules list of rules whe $assignment is 'assignment_rules'
     * @param $isSelfService if the assignment is self service
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    private function createProcess($assignment, $assignedUsers, $assignedGroups, $rules, $isSelfService)
    {
        $bpmn = file_get_contents(__DIR__ . '/processes/AssignmentByProcessVariable.bpmn');
        $bpmn = str_replace('[ASSIGNMENT]', $assignment, $bpmn);
        $bpmn = str_replace('[ASSIGNED_USERS]', $assignedUsers, $bpmn);
        $bpmn = str_replace('[ASSIGNED_GROUPS]', $assignedGroups, $bpmn);
        $bpmn = str_replace('[IS_SELF_SERVICE]', $isSelfService ? 'true' : 'false', $bpmn);

        if ($rules) {
            $rulesAsJson = json_encode($rules);
            $bpmn = str_replace('[ASSIGNMENT_RULES]', htmlspecialchars($rulesAsJson), $bpmn);
        }

        $process = Process::factory()->create([
            'bpmn' => $bpmn,
        ]);

        if ($assignment === 'user_group') {
            foreach (explode(',', $assignedUsers) as $userId) {
                ProcessTaskAssignment::factory()->create([
                    'process_id' => $process->id,
                    'process_task_id' => 'task1_node',
                    'assignment_id' => $userId,
                    'assignment_type' => User::class,
                ]);
            }

            foreach (explode(',', $assignedGroups) as $groupId) {
                ProcessTaskAssignment::factory()->create([
                    'process_id' => $process->id,
                    'process_task_id' => 'task1_node',
                    'assignment_id' => $groupId,
                    'assignment_type' => Group::class,
                ]);
            }
        }

        return $process;
    }

    private function createGroup($numberOfUsers = 1, $status = 'ACTIVE')
    {
        $groupUsers = User::factory()->count($numberOfUsers)->create(['status'=>$status]);
        $group = Group::factory()->create();
        foreach ($groupUsers as $groupUser) {
            GroupMember::factory()->create([
                'member_id' => $groupUser->id,
                'member_type' => User::class,
                'group_id' => $group->id,
            ]);
        }

        return $group;
    }

    private function startTestProcess($process, $processData)
    {
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'start_node']);

        return $this->apiCall('POST', $route, $processData)->json();
    }
}
