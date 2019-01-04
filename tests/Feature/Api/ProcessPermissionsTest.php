<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use \PermissionSeeder;

class ProcessPermissionsTest extends TestCase
{
    use RequestHelper;

    protected $resource = 'requests';

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();

        (new PermissionSeeder)->run($this->user);

        //Permission to use api
        factory(PermissionAssignment::class)->create([
            'permission_id' => Permission::byGuardName('requests.edit')->id,
            'assignable_type' => User::class,
            'assignable_id' => $this->user->id
        ]);
        factory(PermissionAssignment::class)->create([
            'permission_id' => Permission::byGuardName('requests.cancel')->id,
            'assignable_type' => User::class,
            'assignable_id' => $this->user->id
        ]);
    }

    public function testUpdateProcessPermissionRequestCancelTypeUser()
    {
        $process = factory(Process::class)->create();
        $normal_user = factory(User::class)->create([
            'password' => 'password'
        ]);
        // User needs the 'global' requests.cancel first
        $normal_user->giveDirectPermission('requests.cancel');

        $route = route('api.processes.update', [$process->id]);
        $response = $this->apiCall('PUT', $route, [
            'name' => 'Update Process',
            'description' => 'Update Test',
            'cancel_request' => ['users' => [$normal_user->id], 'groups' => []]
        ]);
        $response->assertStatus(200, $response);

        //Verify if user has a permission requests cancel
        $this->assertTrue($normal_user->hasProcessPermission($process, 'requests.cancel'));

        //Verify Process Permission
        $response = ProcessPermission::where('permission_id', Permission::byGuardName('requests.cancel')->id)
            ->where('process_id', $process->id)
            ->where('assignable_id', $normal_user->id)
            ->where('assignable_type', User::class)
            ->exists();

        $this->assertTrue($response);
    }

    public function testUpdateProcessPermissionRequestCancelTypeGroup()
    {
        $this->user->is_administrator = true;
        $normal_user = factory(User::class)->create([
            'password' => 'password'
        ]);
        // User needs the 'global' requests.cancel first
        $normal_user->giveDirectPermission('requests.cancel');

        $process = factory(Process::class)->create();
        $group = factory(Group::class)->create();

        //assign user to group
        $user = factory(User::class)->create();
        factory(GroupMember::class)->create([
            'member_id' => $normal_user->id,
            'member_type' => User::class,
            'group_id' => $group->id
        ]);

        $route = route('api.processes.update', [$process->id]);
        $response = $this->apiCall('PUT', $route, [
            'name' => 'Update Process',
            'description' => 'Update Test',
            'cancel_request' => ['users' => [], 'groups' => [$group->id]]
        ]);
        //The user does not have process permission
        $response->assertStatus(200, $response);

        //Verify if user has a permission requests cancel
        $this->assertTrue($normal_user->hasProcessPermission($process, 'requests.cancel'));

        //Verify Process Permission
        $response = ProcessPermission::where('permission_id', Permission::byGuardName('requests.cancel')->id)
            ->where('process_id', $process->id)
            ->where('assignable_id', $group->id)
            ->where('assignable_type', Group::class)
            ->exists();

        $this->assertTrue($response);
    }

    public function testCancelRequestWithoutProcessPermission()
    {
        $this->resource = 'requests';
        $process = factory(Process::class)->create();

        $request = factory(ProcessRequest::class)->create(['user_id' => $this->user->id, 'process_id' => $process->id]);

        //Canceling request
        $route = route('api.' . $this->resource . '.update', [$request->id]);
        $response = $this->apiCall('PUT', $route, [
            'status' => 'CANCELED',
        ]);
        //The user does not have process permission
        $response->assertStatus(403, $response);
    }

    public function testCancelRequestWithProcessPermission()
    {
        $this->resource = 'requests';
        $process = factory(Process::class)->create();

        //Add Process Permission
        factory(ProcessPermission::class)->create([
            'process_id' => $process->id,
            'permission_id' => Permission::byGuardName('requests.cancel')->id,
            'assignable_type' => User::class,
            'assignable_id' => $this->user->id
        ]);

        $request = factory(ProcessRequest::class)->create(['user_id' => $this->user->id, 'process_id' => $process->id]);

        //Canceling request
        $route = route('api.' . $this->resource . '.update', [$request->id]);
        $response = $this->apiCall('PUT', $route, [
            'status' => 'CANCELED',
        ]);
        //The user have process permission and request is canceled
        $response->assertStatus(204, $response);
    }

    public function testCancelRequestWithUserAdmin()
    {
        //User Administrator.
        $this->user->is_administrator = true;
        $this->user->save();

        $this->resource = 'requests';
        $process = factory(Process::class)->create();

        $request = factory(ProcessRequest::class)->create(['user_id' => $this->user->id, 'process_id' => $process->id]);

        //Canceling request, The user administrator has no restrictions.
        $route = route('api.' . $this->resource . '.update', [$request->id]);
        $response = $this->apiCall('PUT', $route, [
            'status' => 'CANCELED',
        ]);
        //The user have process permission and request is canceled
        $response->assertStatus(204, $response);
    }

}
