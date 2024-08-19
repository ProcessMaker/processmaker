<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessesTest extends TestCase
{
    use RequestHelper;

    protected function withUserSetup()
    {
        // Our user should not be an admin.
        $this->user->is_administrator = false;
        $this->user->save();

        // Seed the permissions table.
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);

        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    public function testIndex()
    {
        $this->user = User::factory()->create([
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

    public function testEdit()
    {
        $process = Process::factory()->create(['name' => 'Test Edit']);

        $this->user = User::factory()->create([
            'is_administrator' => false,
        ]);
        // Set the URL & permission to test.
        $url = 'processes/' . $process->id . '/edit';
        $permission = 'edit-processes';

        // Our user has no permissions, so this should return 403.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(403);

        // Attach the permission to our user.
        $this->user->permissions()->attach(Permission::byName($permission)->id);
        $this->user->refresh();

        // Our user now has permissions, so this should return 200.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(200);
        $response->assertViewIs('processes.edit');
        $response->assertSee('Test Edit');
    }

    public function testCreate()
    {
        $process = Process::factory()->create(['name' => 'Test Create']);

        $this->user = User::factory()->create([
            'is_administrator' => false,
        ]);
        // Set the URL & permission to test.
        $url = 'processes/create';
        $permission = 'create-processes';

        // Our user has no permissions, so this should return 403.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(403);

        // Attach the permission to our user.
        $this->user->permissions()->attach(Permission::byName($permission)->id);
        $this->user->refresh();

        // Our user now has permissions, so this should return 200.
        $response = $this->webCall('GET', $url);
        $response->assertStatus(200);
        $response->assertViewIs('processes.create');
    }

    public function testStore()
    {
        $process = Process::factory()->create(['name' => 'Test Edit']);

        $this->user = User::factory()->create([
            'is_administrator' => false,
        ]);
        // Set the URL, permission, and data with which to test.
        $url = 'processes';
        $permission = 'edit-processes';
        $data = [
            'name' => 'Stored new process',
            'description' => 'My description',
            'status' => 'ACTIVE',
        ];

        // Our user has no permissions, so this should return 403.
        $response = $this->webCall('POST', $url, $data);
        $response->assertStatus(403);

        // Attach the permission to our user.
        $this->user->permissions()->attach(Permission::byName($permission)->id);
        $this->user->refresh();

        // Our user now has permissions, so this should return 200.
        $response = $this->webCall('POST', $url, $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('processes', ['name' => 'Stored new process']);
    }

    public function testUpdate()
    {
        $process = Process::factory()->create(['name' => 'Test Update']);

        $this->user = User::factory()->create([
            'is_administrator' => false,
        ]);
        // Set the URL, permission, and data with which to test.
        $url = 'processes/' . $process->id;
        $permission = 'edit-processes';
        $data = [
            'name' => 'Updated Name',
            'description' => 'My description',
        ];

        // Our user has no permissions, so this should return 403.
        $response = $this->webCall('PUT', $url, $data);
        $response->assertStatus(403);

        // Attach the permission to our user.
        $this->user->permissions()->attach(Permission::byName($permission)->id);
        $this->user->refresh();

        // Our user now has permissions, so this should return 200.
        $response = $this->webCall('PUT', $url, $data);
        $response->assertRedirect('/processes');
        $this->assertDatabaseHas('processes', ['name' => 'Updated Name']);
    }

    public function testArchive()
    {
        $process = Process::factory()->create(['name' => 'Test Archive']);

        $this->user = User::factory()->create([
            'is_administrator' => false,
        ]);
        // Set the URL & permission to test.
        $url = 'processes/' . $process->id;
        $permission = 'archive-processes';

        // Our user has no permissions, so this should return 403.
        $response = $this->webCall('DELETE', $url);
        $response->assertStatus(403);

        // Attach the permission to our user.
        $this->user->permissions()->attach(Permission::byName($permission)->id);
        $this->user->refresh();

        // Our user now has permissions, so this should return 200.
        $response = $this->webCall('DELETE', $url);
        $response->assertRedirect('/processes');
        $this->assertDatabaseMissing('processes', ['id' => $process->id, 'deleted_at' => null]);
    }

    public function testIndexPermissionRedirect()
    {
        $this->user = User::factory()->create();
        $response = $this->webCall('GET', '/processes');
        $response->assertStatus(403);

        foreach (explode('|',
            'view-processes|view-process-categories|view-scripts|view-screens|view-environment_variables'
        ) as $perm) {
            $this->user->permissions()->attach(Permission::byName($perm));
        }
        $this->user->refresh();
        $this->clearPermissionsCache();

        $response = $this->webCall('GET', '/processes');
        $response->assertViewIs('processes.index');

        $checkNextAuth = function ($perm, $nextRoute) {
            $this->user->permissions()->detach(Permission::byName($perm));
            $this->user->refresh();
            $this->flushSession();
            $this->clearPermissionsCache();
            $response = $this->webCall('GET', '/processes');
            $response->assertRedirect(route($nextRoute));
        };
        $checkNextAuth('view-processes', 'process-categories.index');
        $checkNextAuth('view-process-categories', 'scripts.index');
        $checkNextAuth('view-scripts', 'screens.index');
        $checkNextAuth('view-screens', 'environment-variables.index');
    }
}
