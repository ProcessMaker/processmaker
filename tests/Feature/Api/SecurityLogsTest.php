<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use PermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Permission;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;

class SecurityLogsTest extends TestCase
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

    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }

    /**
     * Attempt to access security logs
     */
    public function testAccessSecurityLogsApi()
    {
        $response = $this->apiCall('GET', '/security-logs');
        $response->assertStatus(403);
        
        $permission = Permission::byName('view-security-logs');
        $this->user->permissions()->attach($permission->id);
        $this->user->refresh();
        $this->flushSession();
        
        $response = $this->apiCall('GET', '/security-logs');
        $response->assertStatus(200);
    }
}
