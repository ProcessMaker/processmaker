<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Providers\AuthServiceProvider;
use \PermissionSeeder;

class PermissionsTest extends TestCase
{
    use RequestHelper;

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();
    
        // Seed our tables.
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);
    
        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    public function testApiPermissions()
    {
        $process = factory(Process::class)->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
        ]);
        
        $response = $this->apiCall('GET', '/processes');
        $response->assertStatus(200);
        
        $response = $this->apiCall('GET', '/processes/' . $process->id);
        $response->assertStatus(200);
        
        $permission = Permission::byName('archive-processes');
        $group = Group::where('name', 'All Permissions')->firstOrFail();        
        $group->permissions()->detach($permission->id);
        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('DELETE', '/processes/' . $process->id);
        $response->assertStatus(403);
        
        $this->user->permissions()->attach($permission->id);
        $this->user->refresh();
        $this->flushSession();
        
        $response = $this->apiCall('DELETE', '/processes/' . $process->id);
        $response->assertStatus(204);
    }

    public function testSetPermissionsForUser()
    {
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => true,
        ]);
    
        $testUser = factory(User::class)->create();
        $testPermission = factory(Permission::class)->create();
        $response = $this->apiCall('PUT', '/permissions', [
            'user_id' => $testUser->id,
            'permission_names' => [$testPermission->name]
        ]);
    
        $response->assertStatus(204);
    
        //Assert that the permissions has been set
        $this->assertEquals($testUser->permissions->count(), 1);
        $this->assertEquals($testUser->permissions->first()->id, $testPermission->id);
    }
}
