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
        $processRequestUser = factory(User::class)->create();
        $participantUser = factory(User::class)->create();
        $selfServeUser1 = factory(User::class)->create();
        $selfServeUser2 = factory(User::class)->create();
        $selfServeGroupUser1 = factory(User::class)->create();
        $selfServeGroupUser2 = factory(User::class)->create();
        $userCanCancel = factory(User::class)->create();
        $userCanEdit = factory(User::class)->create();
        $groupUserCanCancel = factory(User::class)->create();
        $groupUserCanEdit = factory(User::class)->create();

        // Groups
        $selfServeGroup = factory(Group::class)->create();
        $canCancelGroup = factory(Group::class)->create();
        $canEditGroup = factory(Group::class)->create();
        $this->addUserToGroup($selfServeGroupUser1, $selfServeGroup);
        $this->addUserToGroup($selfServeGroupUser2, $selfServeGroup);
        $this->addUserToGroup($groupUserCanCancel, $canCancelGroup);
        $this->addUserToGroup($groupUserCanEdit, $canEditGroup);

        // Process Request
        $process = factory(Process::class)->create([
        ]);
        $process->usersCanCancel()->attach($userCanCancel);
        $process->groupsCanCancel()->attach($canCancelGroup);
        $process->usersCanEditData()->attach($userCanEdit);
        $process->groupsCanEditData()->attach($canEditGroup);
        $processRequest = factory(ProcessRequest::class)->create([
            'process_id' => $process->id,
            'user_id' => $processRequestUser->id,
        ]);
        $processRequestToken = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $processRequest->id,
            'user_id' => $participantUser->id,
        ]);
        $selfServeGroupToken = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $processRequest->id,
            'is_self_service' => true,
            'self_service_groups' => ['groups' => [$selfServeGroup->id]]
        ]);
        $selfServeUserToken = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $processRequest->id,
            'is_self_service' => true,
            'self_service_groups' => ['users' => [$selfServeUser1->id, $selfServeUser2->id]]
        ]);

        $this->assertEquals(0, RequestUserPermission::where('request_id', $processRequest->id)->count());
        UpdateRequestUserPermissions::dispatch($processRequest->id);

        $users = RequestUserPermission::where('request_id', $processRequest->id)->pluck('user_id');
        // $this->assertEquals(8, $users->count());
        $this->assertTrue($users->contains($selfServeUser1->id));
        $this->assertTrue($users->contains($selfServeUser1->id));
        $this->assertTrue($users->contains($selfServeGroupUser1->id));
        $this->assertTrue($users->contains($selfServeGroupUser2->id));
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