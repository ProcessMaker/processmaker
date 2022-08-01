<?php
namespace Tests;
use ProcessMaker\Jobs\UpdateRequestUserPermissions;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Models\RequestUserPermission;
use Illuminate\Support\Facades\Event;

class UpdateRequestUserPermissionsTest extends TestCase
{
    public function test()
    {

        // Users
        $params = ['status' => 'ACTIVE'];
        $processRequestUser = factory(User::class)->create($params);
        $participantUser = factory(User::class)->create($params);
        $selfServeUser1 = factory(User::class)->create($params);
        $selfServeUser2 = factory(User::class)->create($params);
        $selfServeGroupUser1 = factory(User::class)->create($params);
        $selfServeGroupUser2 = factory(User::class)->create($params);
        $selfServeGroupUser3 = factory(User::class)->create($params);
        $userCanCancel = factory(User::class)->create($params);
        $userCanEdit = factory(User::class)->create($params);
        $groupUserCanCancel = factory(User::class)->create($params);
        $groupUserCanEdit = factory(User::class)->create($params);

        // Groups
        $selfServeGroup = factory(Group::class)->create(['status' => 'ACTIVE']);
        $selfServeGroup2 = factory(Group::class)->create(['status' => 'ACTIVE']);
        $canCancelGroup = factory(Group::class)->create(['status' => 'ACTIVE']);
        $canEditGroup = factory(Group::class)->create(['status' => 'ACTIVE']);
        $this->addUserToGroup($selfServeGroupUser1, $selfServeGroup);
        $this->addUserToGroup($selfServeGroupUser2, $selfServeGroup);
        $this->addUserToGroup($selfServeGroupUser3, $selfServeGroup2);
        $this->addUserToGroup($groupUserCanCancel, $canCancelGroup);
        $this->addUserToGroup($groupUserCanEdit, $canEditGroup);

        // Process Request
        $process = factory(Process::class)->create([]);
        $processWithPermissions = factory(Process::class)->create([]);
        $processWithPermissions->usersCanCancel()->attach($userCanCancel, ['method' => 'CANCEL']);
        $processWithPermissions->groupsCanCancel()->attach($canCancelGroup, ['method' => 'CANCEL']);
        $processWithPermissions->usersCanEditData()->attach($userCanEdit, ['method' => 'EDIT_DATA']);
        $processWithPermissions->groupsCanEditData()->attach($canEditGroup, ['method' => 'EDIT_DATA']);

        ProcessRequest::unsetEventDispatcher(); // Run after tokens have been created

        $userProcessRequest = factory(ProcessRequest::class)->create([
            'process_id' => $process->id,
            'user_id' => $processRequestUser->id,
            'callable_id' => 'node_1',
            'process_collaboration_id' => null,
        ]);
        $participantProcessRequest = factory(ProcessRequest::class)->create([
            'user_id' => null,
            'process_id' => $process->id,
            'callable_id' => 'node_1',
            'process_collaboration_id' => null,
        ]);
        $selfServeGroupProcessRequest = factory(ProcessRequest::class)->create([
            'user_id' => null,
            'process_id' => $process->id,
            'callable_id' => 'node_1',
            'process_collaboration_id' => null,
        ]);
        $selfServeUserProcessRequest = factory(ProcessRequest::class)->create([
            'user_id' => null,
            'process_id' => $process->id,
            'callable_id' => 'node_1',
            'process_collaboration_id' => null,
        ]);
        $processWithPermissionsRequest = factory(ProcessRequest::class)->create([
            'user_id' => null,
            'process_id' => $processWithPermissions->id,
            'callable_id' => 'node_1',
            'process_collaboration_id' => null,
        ]);

        $processRequestToken = factory(ProcessRequestToken::class)->create([
            'user_id' => $participantUser->id,
            'process_id' => $process->id,
            'process_request_id' => $participantProcessRequest->id,
        ]);
        $selfServeGroupToken = factory(ProcessRequestToken::class)->create([
            'user_id' => null,
            'process_id' => $process->id,
            'process_request_id' => $selfServeGroupProcessRequest->id,
            'is_self_service' => true,
            'self_service_groups' => ['groups' => [ (string) $selfServeGroup->id]]
        ]);
        $selfServeUserToken = factory(ProcessRequestToken::class)->create([
            'user_id' => null,
            'process_id' => $process->id,
            'process_request_id' => $selfServeUserProcessRequest->id,
            'is_self_service' => true,
            'self_service_groups' => [
                'users' => [ (string) $selfServeUser1->id, (string) $selfServeUser2->id ],
                'groups' => [ (string) $selfServeGroup2->id ]
            ]
        ]);

        // Self Serve with one group
        UpdateRequestUserPermissions::dispatch($selfServeGroupProcessRequest->id);
        $users = RequestUserPermission::where('request_id', $selfServeGroupProcessRequest->id)->pluck('user_id');
        $this->assertCount(2, $users);
        $this->assertContains($selfServeGroupUser1->id, $users);
        $this->assertContains($selfServeGroupUser2->id, $users);

        // Process with userCan/groupCan permissions
        UpdateRequestUserPermissions::dispatch($processWithPermissionsRequest->id);
        $users = RequestUserPermission::where('request_id', $processWithPermissionsRequest->id)->pluck('user_id');
        $this->assertCount(4, $users);
        $this->assertContains($groupUserCanCancel->id, $users);
        $this->assertContains($groupUserCanEdit->id, $users);
        $this->assertContains($userCanCancel->id, $users);
        $this->assertContains($userCanEdit->id, $users);

        // Self Serve with two groups, each with 1 user, and one user
        UpdateRequestUserPermissions::dispatch($selfServeUserProcessRequest->id);
        $users = RequestUserPermission::where('request_id', $selfServeUserProcessRequest->id)->pluck('user_id');
        $this->assertCount(3, $users);
        $this->assertContains($selfServeUser1->id, $users);
        $this->assertContains($selfServeUser2->id, $users);
        $this->assertContains($selfServeGroupUser3->id, $users);
        
        // Self Serve with one group that has 2 users
        UpdateRequestUserPermissions::dispatch($selfServeGroupProcessRequest->id);
        $users = RequestUserPermission::where('request_id', $selfServeGroupProcessRequest->id)->pluck('user_id');
        $this->assertCount(2, $users);
        $this->assertContains($selfServeGroupUser1->id, $users);
        $this->assertContains($selfServeGroupUser2->id, $users);
        
        // Participants
        UpdateRequestUserPermissions::dispatch($participantProcessRequest->id);
        $users = RequestUserPermission::where('request_id', $participantProcessRequest->id)->pluck('user_id');
        $this->assertCount(1, $users);
        $this->assertContains($participantUser->id, $users);
        
        // Request starter 
        UpdateRequestUserPermissions::dispatch($userProcessRequest->id);
        $users = RequestUserPermission::where('request_id', $userProcessRequest->id)->pluck('user_id');
        $this->assertCount(1, $users);
        $this->assertContains($processRequestUser->id, $users);
    }

    private function addUserToGroup(User $user, Group $group)
    {
        factory(GroupMember::class)->create([
            'member_id' => $user->id,
            'member_type' => User::class,
            'group_id' => $group->id
        ]);
    }
}