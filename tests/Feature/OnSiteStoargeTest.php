<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Providers\AuthServiceProvider;
use ProcessMaker\Models\Permission;

class OnSiteStoargeTest extends TestCase
{
    use RequestHelper;



    public function requestDataIsStoredInExternalConnection()
    {
        $this->user = factory(User::class)->create([
            'is_administrator' => false,
        ]);
        // Set the URL & permission to test.
        $url = '/processes';
        $permission = 'view-processes';
        
        // Our user has no permissions, so this should return 403.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(403);

        // Attach the permission to our user.
        $this->user->permissions()->attach(Permission::byName($permission)->id);
        $this->user->refresh();

        // Our user now has permissions, so this should return 200.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(200);
        $response->assertViewIs('processes.index');
        $response->assertSee('Processes');
    }

}
