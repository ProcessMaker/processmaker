<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Additional tests in tests/Traits/ForUserScopeTest.php
 */
class ProcessRequestPolicyTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    public function withUserSetup()
    {
        // Make $this->user a regular user instead of an admin user
        $this->user = User::factory()->create();
    }

    public function testUserHasParticipated()
    {
        $request = ProcessRequest::factory()->create();

        $route = route('api.requests.show', [$request]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(403);

        // Make user a participant
        ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_request_id' => $request->id,
        ]);

        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
    }

    public function testUserHasPermission()
    {
        $request = ProcessRequest::factory()->create();

        $route = route('api.requests.show', [$request]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(403);

        $this->user->giveDirectPermission('view-all_requests');
        $this->user->refresh();

        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
    }
}
