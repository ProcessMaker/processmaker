<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Hash;
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

        // (new PermissionSeeder)->run($this->user);

        // //Permission to use api
        // factory(PermissionAssignment::class)->create([
        //     'permission_id' => Permission::byName('requests.edit')->id,
        //     'assignable_type' => User::class,
        //     'assignable_id' => $this->user->id
        // ]);
        // factory(PermissionAssignment::class)->create([
        //     'permission_id' => Permission::byName('requests.cancel')->id,
        //     'assignable_type' => User::class,
        //     'assignable_id' => $this->user->id
        // ]);
    }

    public function testUpdateProcessPermissionRequestCancelTypeUser()
    {
        $this->markTestSkipped('API permissions not yet implemented');
        $process = factory(Process::class)->create();
        $normal_user = factory(User::class)->create([
            'password' => Hash::make('password')
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
        $this->assertTrue($normal_user->hasPermissionsFor($process, 'requests.cancel'));

        //Verify Process Permission
        $response = ProcessPermission::where('permission_id', Permission::byName('requests.cancel')->id)
            ->where('process_id', $process->id)
            ->where('assignable_id', $normal_user->id)
            ->where('assignable_type', User::class)
            ->exists();

        $this->assertTrue($response);
    }

}
