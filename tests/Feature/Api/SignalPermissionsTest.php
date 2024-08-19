<?php

namespace Tests\Feature\Api;

use Database\Seeders\SignalSeeder;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SignalPermissionsTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    private function createUser()
    {
        $this->user = User::factory()->create(['status' => 'ACTIVE']);
    }

    public function setUpSignalAssets(): void
    {
        ProcessCategory::factory()->create(['is_system' => true]);
        (new SignalSeeder)->run();
    }

    private function assertForbidden($method, $url, $data = [])
    {
        $response = $this->apiCall($method, $url, $data);
        $response->assertStatus(403);
    }

    private function assertSuccess($method, $url, $data = [])
    {
        $response = $this->apiCall($method, $url, $data);
        $response->assertStatus(200);
    }

    private function givePermission($permission)
    {
        $this->user->giveDirectPermission($permission);
        $this->user->refresh();
        $this->clearAndRebuildUserPermissionsCache();
        $this->flushSession();
    }

    public function testViewPermission()
    {
        $this->createUser();
        $indexRoute = route('api.signals.index');
        $showRoute = route('api.signals.show', 'anything');
        $this->assertForbidden('GET', $indexRoute);
        $this->assertForbidden('GET', $showRoute);
        $this->givePermission('view-signals');
        $this->assertSuccess('GET', $indexRoute);
        $this->assertSuccess('GET', $showRoute);
    }

    public function testCreatePermission()
    {
        $this->createUser();
        $storeRoute = route('api.signals.store');
        $data = [
            'id' => 'anything',
            'name' => 'anything',
        ];
        $this->assertForbidden('POST', $storeRoute, $data);
        $this->givePermission('create-signals');
        $this->assertSuccess('POST', $storeRoute, $data);
    }

    public function testEditPermission()
    {
        $this->createUser();
        $this->givePermission('create-signals');
        $storeRoute = route('api.signals.store');
        $data = [
            'id' => 'anything',
            'name' => 'anything',
        ];
        $this->assertSuccess('POST', $storeRoute, $data);

        $data['name'] = 'other';
        $updateRoute = route('api.signals.update', 'anything');
        $this->assertForbidden('PUT', $updateRoute, $data);
        $this->givePermission('edit-signals');
        $this->assertSuccess('PUT', $updateRoute, $data);
    }

    public function testDeletePermission()
    {
        $this->createUser();
        $this->givePermission('create-signals');
        $storeRoute = route('api.signals.store');
        $data = [
            'id' => 'anything',
            'name' => 'anything',
        ];
        $this->assertSuccess('POST', $storeRoute, $data);

        $data['name'] = 'other';
        $deleteRoute = route('api.signals.destroy', 'anything');
        $response = $this->apiCall('DELETE', $deleteRoute);
        $response->assertStatus(403);

        $this->givePermission('delete-signals');

        $response = $this->apiCall('DELETE', $deleteRoute);
        $response->assertStatus(201);
    }

    public function testWebViewPermission()
    {
        $this->createUser();
        $indexRoute = route('signals.index');
        $response = $this->webCall('GET', $indexRoute);
        $response->assertStatus(403);
        $this->givePermission('view-signals');
        $response = $this->webCall('GET', $indexRoute);
        $response->assertStatus(200);
    }

    public function testWebEditPermission()
    {
        $storeRoute = route('api.signals.store');
        $data = [
            'id' => 'anything',
            'name' => 'anything',
        ];
        $this->apiCall('POST', $storeRoute, $data);

        $this->createUser();
        $indexRoute = route('signals.edit', 'anything');
        $response = $this->webCall('GET', $indexRoute);
        $response->assertStatus(403);
        $this->givePermission('edit-signals');
        $response = $this->webCall('GET', $indexRoute);
        $response->assertStatus(200);
    }
}
