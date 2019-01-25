<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Providers\AuthServiceProvider;
use ProcessMaker\Models\Permission;

class Dashboard extends TestCase
{
    use RequestHelper;
    
    protected function withUserSetup()
    {
        // Seed the permissions table.
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);
        
        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    public function testIndexRoute()
    {
        $this->user = factory(User::class)->create();
        $response = $this->webCall('GET', '/admin');
        $response->assertStatus(403);

        $this->user->permissions()->attach(Permission::byName('view-users'));
        $this->user->permissions()->attach(Permission::byName('view-groups'));
        $this->user->refresh();

        $response = $this->webCall('GET', '/admin');
        $response->assertRedirect(route('users.index'));

        $this->user->permissions()->detach(Permission::byName('view-users'));
        $this->user->refresh();
        $this->flushSession();
        
        $response = $this->webCall('GET', '/admin');
        $response->assertRedirect(route('groups.index'));
    }
}
