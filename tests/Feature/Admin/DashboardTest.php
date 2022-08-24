<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DashboardTest extends TestCase
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
        $this->user = User::factory()->create();
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
