<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Models\ProcessCategory;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Providers\AuthServiceProvider;
use ProcessMaker\Models\Permission;

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

    public function testIndexWithOutPermission()
    {
        $this->user = factory(User::class)->create([
            'is_administrator' => false,
        ]);
        $response = $this->webGet('/processes');
        $response->assertStatus(403);
    }

    public function testEdit()
    {
        $process = factory(Process::class)->create(['name' => 'Test Edit']);

        $this->user = factory(User::class)->create([
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

    public function testEditWithOutPermission()
    {
        
        $this->user = factory(User::class)->create([
            'is_administrator' => false,
        ]);
        (new \PermissionSeeder())->run($this->user);

        $process = factory(Process::class)->create(['name' => 'Test Edit']);
        $response = $this->webGet('processes/' . $process->id . '/edit');
        $response->assertStatus(403);
    }

    public function testCreate()
    {
        $process = factory(Process::class)->create(['name' => 'Test Create']);
        $response = $this->webGet('/processes/create');
        $response->assertViewIs('processes.create');
        $response->assertStatus(200);
    }

    public function testCreateWithOutPermission()
    {
        
        $this->user = factory(User::class)->create([
            'is_administrator' => false,
        ]);
        $process = factory(Process::class)->create(['name' => 'Test Create']);
        $response = $this->webGet('/processes/create');
        $response->assertStatus(403);
    }

    public function testStore()
    {
        $this->withoutExceptionHandling();
        $response = $this->webCall('POST', '/processes', [
            'name' => 'Stored new user',
            'description' => 'descript',
            'status' => 'ACTIVE'
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('processes', ['name' => 'Stored new user']);  // how do I verify DB table name?

    }

    public function testUpdate()
    {
        $process = factory(Process::class)->create(['name' => 'Test Update']);
        $response = $this->webCall('PUT', 'processes/' . $process->id . '', [
            'name' => 'Update Name',
            'description' => 'Descriptionnnnn'
        ]);
        $this->assertDatabaseHas('processes', ['name' => 'Update Name']);
        $response->assertRedirect('/processes');
    }

    public function testDestroy()
    {
        $process = factory(Process::class)->create();
        $response = $this->webCall('DELETE', 'processes/' . $process->id . '');
        $this->assertDatabaseMissing('processes', ['id' => $process->id, 'deleted_at' => null]);
        $response->assertRedirect('/processes');
    }

    public function testArchiveWithOutPermission()
    {
        
        $this->user = factory(User::class)->create([
            'is_administrator' => false,
        ]);
        $process = factory(Process::class)->create();
        $response = $this->webCall('DELETE', 'processes/' . $process->id . '');
        $response->assertStatus(403);
    }
}
