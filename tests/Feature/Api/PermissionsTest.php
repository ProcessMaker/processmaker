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
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\ScreenCategory;
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
        $group = factory(Group::class)->create([
            'name' => 'All Permissions',
        ]);
        $permissions = Permission::all()->pluck('id');
        $group->permissions()->attach($permissions);
        $group->save();
        
        factory(GroupMember::class)->create([
            'group_id' => $group->id,
            'member_type' => User::class,
            'member_id' => $this->user->id,
        ]);
        
        $this->user->refresh();
        $this->flushSession();        
        
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

    public function testCategoryPermission()
    {
        $context = function($type, $class) {
            $attrs = ['name' => 'Test Category', 'status' => 'ACTIVE'];
            $url = route("api.{$type}_categories.store");
            $response = $this->apiCall('POST', $url, $attrs);
            $response->assertStatus(403);

            // Now give the user permission via a group
            $group = factory(Group::class)->create([
                'name' => 'Test',
            ]);
            factory(GroupMember::class)->create([
                'group_id' => $group->id,
                'member_type' => User::class,
                'member_id' => $this->user->id,
            ]);

            $permission = Permission::byName("create-{$type}-categories");
            $group->permissions()->attach($permission);
            $this->user->refresh();
            $this->flushSession();
            
            // test create permission
            $response = $this->apiCall('POST', $url, $attrs);
            $response->assertStatus(201);

            // test update permission
            $id = $response->json()['id'];
            $attrs = ['name' => 'Test Category Update', 'status' => 'ACTIVE'];
            $url = route("api.{$type}_categories.update", $id); 
            $response = $this->apiCall('PUT', $url, $attrs);
            $response->assertStatus(403);
            
            $permission = Permission::byName("edit-{$type}-categories");
            $group->permissions()->attach($permission);
            $this->user->refresh();
            $this->flushSession();

            $response = $this->apiCall('PUT', $url, $attrs);
            $this->assertEquals('Test Category Update', $class::find($id)->name);
            
            // test view permission
            $url = route("api.{$type}_categories.index"); 
            $response = $this->apiCall('GET', $url);
            $response->assertStatus(403);
            
            $url = route("api.{$type}_categories.show", $id); 
            $response = $this->apiCall('GET', $url);
            $response->assertStatus(403);
            
            $permission = Permission::byName("view-{$type}-categories");
            $group->permissions()->attach($permission);
            $this->user->refresh();
            $this->flushSession();
            
            $url = route("api.{$type}_categories.index"); 
            $response = $this->apiCall('GET', $url);
            $response->assertStatus(200);

            $url = route("api.{$type}_categories.show", $id); 
            $response = $this->apiCall('GET', $url);
            $response->assertStatus(200);

            // test delete permission
            $url = route("api.{$type}_categories.destroy", $id); 
            $response = $this->apiCall('DELETE', $url);
            $response->assertStatus(403);

            $permission = Permission::byName("view-{$type}-categories");
            $group->permissions()->attach($permission);
            $this->user->refresh();
            $this->flushSession();
            
            $response = $this->apiCall('DELETE', $url);
            $response->assertStatus(403);
        };

        $context('process', ProcessCategory::class);
        $context('script', ScriptCategory::class);
        $context('screen', ScreenCategory::class);

    }
}
