<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use ProcessMaker\Models\Process;
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

    public function testUserStartedProcessRequest()
    {
        $request = ProcessRequest::factory()->create(['user_id' => $this->user->id]);
        $anotherUser = User::factory()->create();
        $anotherRequest = ProcessRequest::factory()->create(['user_id' => $anotherUser->id]);

        $route = route('api.requests.show', [$anotherRequest]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(403);

        $route = route('api.requests.show', [$request]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
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

    public function testProcessManagerCanViewManagedRequestAndEndEventDestination()
    {
        $firstManager = User::factory()->create();
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../../Fixtures/action_by_email_process_no_require_login.bpmn'),
            'manager_id' => [$firstManager->id, $this->user->id],
        ]);
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'status' => 'COMPLETED',
        ]);
        ProcessRequestToken::factory()->create([
            'element_id' => 'node_18',
            'element_type' => 'end_event',
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'status' => 'CLOSED',
        ]);

        $this->apiCall('GET', route('api.requests.show', [$request]))
            ->assertStatus(200);

        $this->apiCall('GET', route('api.requests.end_event_destination', [$request]))
            ->assertStatus(200)
            ->assertJsonPath('data.endEventDestination.type', 'summaryScreen')
            ->assertJsonPath('data.endEventDestination.value', null);
    }

    public function testProcessManagerCannotViewRequestFromUnmanagedProcess()
    {
        $managedProcess = Process::factory()->create([
            'manager_id' => [$this->user->id],
        ]);
        $unmanagedRequest = ProcessRequest::factory()->create();

        $this->assertNotEquals($managedProcess->id, $unmanagedRequest->process_id);

        $this->apiCall('GET', route('api.requests.show', [$unmanagedRequest]))
            ->assertStatus(403);

        $this->apiCall('GET', route('api.requests.end_event_destination', [$unmanagedRequest]))
            ->assertStatus(403);
    }
}
