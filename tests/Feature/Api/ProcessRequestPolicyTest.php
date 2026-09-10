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

    public function testProcessManagerAccessIsRevokedWhenRemovedFromProcess()
    {
        $process = Process::factory()->create([
            'manager_id' => [$this->user->id],
        ]);
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        $this->apiCall('GET', route('api.requests.show', [$request]))
            ->assertStatus(200);

        $process->manager_id = [];
        $process->save();

        $this->apiCall('GET', route('api.requests.show', [$request]))
            ->assertStatus(403);
    }

    public function testProcessManagerCannotUpdateOrDeleteManagedRequest()
    {
        $process = Process::factory()->create([
            'manager_id' => [$this->user->id],
        ]);
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        $this->apiCall('PUT', route('api.requests.update', [$request]), [
            'name' => 'Unauthorized update',
        ])->assertStatus(403);

        $this->apiCall('DELETE', route('api.requests.destroy', [$request]))
            ->assertStatus(403);
    }

    public function testManagedRequestIsNotAddedToRequestListing()
    {
        $process = Process::factory()->create([
            'manager_id' => [$this->user->id],
        ]);
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        $this->apiCall('GET', route('api.requests.show', [$request]))
            ->assertStatus(200);

        $this->apiCall('GET', route('api.requests.index'))
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $request->id]);
    }

    public function testScalarProcessManagerValueIsNormalizedForAuthorization()
    {
        $process = Process::factory()->create([
            'manager_id' => $this->user->id,
        ]);
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        $this->assertSame([$this->user->id], $process->fresh()->manager_id);

        $this->apiCall('GET', route('api.requests.show', [$request]))
            ->assertStatus(200);
    }

    public function testProcessManagerReassignmentUpdatesRequestAccess()
    {
        $newManager = User::factory()->create();
        $process = Process::factory()->create([
            'manager_id' => [$this->user->id],
        ]);
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        $this->apiCall('GET', route('api.requests.show', [$request]))
            ->assertStatus(200);

        $process->manager_id = [$newManager->id];
        $process->save();

        $this->apiCall('GET', route('api.requests.show', [$request]))
            ->assertStatus(403);

        $this->user = $newManager;

        $this->apiCall('GET', route('api.requests.show', [$request]))
            ->assertStatus(200);
    }

    public function testProcessManagerCanViewManagedRequestsInEveryStatus()
    {
        $process = Process::factory()->create([
            'manager_id' => [$this->user->id],
        ]);

        foreach (['ACTIVE', 'COMPLETED', 'CANCELED', 'ERROR'] as $status) {
            $request = ProcessRequest::factory()->create([
                'process_id' => $process->id,
                'status' => $status,
            ]);

            $this->apiCall('GET', route('api.requests.show', [$request]))
                ->assertStatus(200);
        }
    }

    public function testEndEventDestinationReturnsNullWhenManagedRequestHasNoEndEvent()
    {
        $process = Process::factory()->create([
            'manager_id' => [$this->user->id],
        ]);
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'status' => 'COMPLETED',
        ]);

        $this->apiCall('GET', route('api.requests.end_event_destination', [$request]))
            ->assertStatus(200)
            ->assertJsonPath('data', null);
    }

    public function testEndEventDestinationReturnsConfiguredExternalUrlForProcessManager()
    {
        $bpmn = file_get_contents(__DIR__ . '/../../Fixtures/action_by_email_process_no_require_login.bpmn');
        $bpmn = str_replace(
            '{&#34;type&#34;:&#34;summaryScreen&#34;,&#34;value&#34;:null}',
            '{&#34;type&#34;:&#34;externalURL&#34;,&#34;value&#34;:&#34;https://example.com/summary&#34;}',
            $bpmn,
        );
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
            'manager_id' => [$this->user->id],
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

        $this->apiCall('GET', route('api.requests.end_event_destination', [$request]))
            ->assertStatus(200)
            ->assertJsonPath('data.endEventDestination.type', 'externalURL')
            ->assertJsonPath('data.endEventDestination.value', 'https://example.com/summary');
    }
}
