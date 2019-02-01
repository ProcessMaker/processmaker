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
    
    public $withPermissions = true;

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();
        
        (new PermissionSeeder)->run($this->user);
    }

    public function testUpdateProcessPermissionRequestCancelTypeUser()
    {
        // Create a process
        $process = factory(Process::class)->create();
        
        // Create a "normal" user
        $normalUser = factory(User::class)->create([
            'password' => Hash::make('password')
        ]);
        
        // We haven't assigned cancel permissions to this process, so let's
        // assert that our "normal" user cannot cancel it
        $this->assertFalse($normalUser->can('cancel', $process));

        // Ensure our primary user can edit processes
        $this->user->giveDirectPermission('edit-processes');
        $this->user->refresh();
        $this->flushSession();

        // Add the "normal" user to the list of users that have permission to
        // cancel the process
        $route = route('api.processes.update', [$process->id]);
        $response = $this->apiCall('PUT', $route, [
            'name' => 'Update Process',
            'description' => 'Update Test',
            'cancel_request' => ['users' => [$normalUser->id], 'groups' => []]
        ]);
        
        // Assert that the API returned a valid response
        $response->assertStatus(200, $response);

        // Assert that our "normal user" can now cancel the process
        $process->refresh();
        $this->assertTrue($normalUser->can('cancel', $process));
    }

}
