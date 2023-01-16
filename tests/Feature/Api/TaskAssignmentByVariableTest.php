<?php

namespace Tests\Feature\Api;

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

class TaskAssignmentByVariableTest extends TestCase
{
    use RequestHelper;

    public function testProcessVariableAssignmentWithSimpleIds()
    {
        // Create users of a group and a user without group
        $user = User::factory()->create(['status'=>'ACTIVE']);
        $groupUsers = User::factory()->count(5)->create(['status'=>'ACTIVE']);
        $allUsers = array_merge([$user->id], $groupUsers->pluck('id')->toArray());
        sort($allUsers);
        $group = Group::factory()->create();
        foreach ($groupUsers as $groupUser) {
            GroupMember::factory()->create([
                'member_id' => $groupUser->id,
                'member_type' => User::class,
                'group_id' => $group->id,
            ]);
        }

        $bpmn = file_get_contents(__DIR__ . '/processes/AssignmentByProcessVariable.bpmn');
        $bpmn = str_replace('[IS_SELF_SERVICE]', 'false', $bpmn);
        $process = Process::factory()->create([
            'bpmn' => $bpmn
        ]);


        $runProcess = function ($processData) use ($process) {
            $route = route('api.process_events.trigger',
                [$process->id, 'event' => 'start_node']);

            return $this->apiCall('POST', $route, $processData)->json();
        };

        // The first assignment should be to user (is the first created user)
        $response = $runProcess(['usersVariable' => $user->id, 'groupsVariable' => $group->id]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where([ 'process_request_id' => $requestId, 'status' => 'ACTIVE', ])->firstOrFail();
        $this->assertEquals($user->id, $task->user_id);

        // The second assignment shoudl be to the first user of the created group
        $response = $runProcess(['usersVariable' => $user->id, 'groupsVariable' => $group->id]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where([ 'process_request_id' => $requestId, 'status' => 'ACTIVE', ])->firstOrFail();
        $this->assertEquals($groupUsers->first()->id, $task->user_id);
    }

    public function testProcessVariableAssignmentWithArrayOfIds()
    {
        // Create users of a group and a user without group
        $users = User::factory(2)->create(['status'=>'ACTIVE']);
        $groupUsers1 = User::factory()->count(6)->create(['status'=>'ACTIVE']);
        $groupUsers2 = User::factory()->count(5)->create(['status'=>'ACTIVE']);
        $allUsers = array_merge($users->pluck('id')->toArray(),
        $groupUsers1->pluck('id')->toArray(),
        $groupUsers2->pluck('id')->toArray());
        sort($allUsers);
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        foreach ($groupUsers1 as $groupUser) {
            GroupMember::factory()->create([ 'member_id' => $groupUser->id, 'member_type' => User::class, 'group_id' => $group1->id, ]);
        }
        foreach ($groupUsers2 as $groupUser) {
            GroupMember::factory()->create([ 'member_id' => $groupUser->id, 'member_type' => User::class, 'group_id' => $group2->id, ]);
        }

        $bpmn = file_get_contents(__DIR__ . '/processes/AssignmentByProcessVariable.bpmn');
        $bpmn = str_replace('[IS_SELF_SERVICE]', 'false', $bpmn);
        $process = Process::factory()->create([
            'bpmn' => $bpmn
        ]);

        $runProcess = function ($processData) use ($process) {
            $route = route('api.process_events.trigger',
                [$process->id, 'event' => 'start_node']);

            return $this->apiCall('POST', $route, $processData)->json();
        };

        // The first assignment should be to user (is the first created user)
        $response = $runProcess(['usersVariable' => $users->pluck('id')->toArray(), 'groupsVariable' => [$group1->id, $group2->id]]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where([ 'process_request_id' => $requestId, 'status' => 'ACTIVE', ])->firstOrFail();
        $this->assertEquals($users->first()->id, $task->user_id);

        // The second assignment should be to the second user
        $response = $runProcess(['usersVariable' => $users->pluck('id')->toArray(), 'groupsVariable' => [$group1->id, $group2->id]]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where([ 'process_request_id' => $requestId, 'status' => 'ACTIVE', ])->firstOrFail();
        $this->assertEquals($users->get(1)->id, $task->user_id);

        // The third assignment should be to the first user of the first created group
        $response = $runProcess(['usersVariable' => $users->pluck('id')->toArray(), 'groupsVariable' => [$group1->id, $group2->id]]);
        $requestId = $response['id'];
        $task = ProcessRequestToken::where([ 'process_request_id' => $requestId, 'status' => 'ACTIVE', ])->firstOrFail();
        $this->assertEquals($groupUsers1->first()->id, $task->user_id);
    }


    public function testSelfServiceWithProcessVariableAssignment()
    {
        // Create users of a group and a user without group
        $user = User::factory()->create(['status'=>'ACTIVE']);
        $groupUsers = User::factory()->count(5)->create(['status'=>'ACTIVE']);
        $allUsers = array_merge([$user->id], $groupUsers->pluck('id')->toArray());
        sort($allUsers);
        $group = Group::factory()->create();
        foreach ($groupUsers as $groupUser) {
            GroupMember::factory()->create([
                'member_id' => $groupUser->id,
                'member_type' => User::class,
                'group_id' => $group->id,
            ]);
        }

        $bpmn = file_get_contents(__DIR__ . '/processes/AssignmentByProcessVariable.bpmn');
        $bpmn = str_replace('[IS_SELF_SERVICE]', 'true', $bpmn);
        $process = Process::factory()->create([
            'bpmn' => $bpmn
        ]);

        $runProcess = function ($processData) use ($process) {
            $route = route('api.process_events.trigger',
                [$process->id, 'event' => 'start_node']);
            return $this->apiCall('POST', $route, $processData)->json();
        };

        $response = $runProcess(['usersVariable' => $user->id, 'groupsVariable' => [$group->id]]);
        $requestId = $response['id'];

        $task = ProcessRequestToken::where([ 'process_request_id' => $requestId, 'status' => 'ACTIVE', ])->firstOrFail();
        $this->assertEquals(1, $task->is_self_service);
        // As it is self service the user must be null
        $this->assertEquals(null, $task->user_id);
        // The column self_service_groups should have the configured groups and users
        $this->assertEquals(["users" => [$user->id], "groups" =>[$group->id]], $task->self_service_groups);
    }
}
