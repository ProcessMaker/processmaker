<?php

namespace Tests\Traits;

use Database\Seeders\PermissionSeeder;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\RequestUserPermission;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ForUserScopeTest extends TestCase
{
    use RequestHelper;

    public function setUpRequests()
    {
        $this->useSameDBConnection();

        (new PermissionSeeder)->run();
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    protected function withUserSetUp()
    {
        // Make $this->user a regular user insetad of an admin
        $this->user = User::factory()->create();
    }

    public function testUserHasParticipated()
    {
        $this->startedbyUser = ProcessRequest::factory()->create(['user_id' => $this->user->id]);
        $this->userHasParticipated = ProcessRequest::factory()->create();
        ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_request_id' => $this->userHasParticipated->id,
        ]);

        $this->assertEquals([
            $this->startedbyUser->id,
            $this->userHasParticipated->id,
        ], $this->requestIds());
    }

    public function testUserHasViewPermission()
    {
        $request = ProcessRequest::factory()->create();
        $this->user->giveDirectPermission('view-all_requests');

        $this->assertEquals([$request->id], $this->requestIds());
    }

    public function testUserHasEditPermission()
    {
        $request = ProcessRequest::factory()->create();
        $this->user->giveDirectPermission('edit-request_data');

        $this->assertEquals([$request->id], $this->requestIds());
    }

    public function testUserHasEditProccessDataPermission()
    {
        $group = Group::factory()->create();
        $process1 = Process::factory()->create();
        $process1->usersCanEditData()->sync([$this->user->id => ['method' => 'EDIT_DATA']]);
        $process2 = Process::factory()->create();
        $process2->groupsCanEditData()->sync([$group->id => ['method' => 'EDIT_DATA']]);

        $request1 = ProcessRequest::factory()->create(['process_id' => $process1]);
        $request2 = ProcessRequest::factory()->create(['process_id' => $process2]);

        $this->user->groups()->sync([$group->id]);

        $this->assertEquals([$request1->id, $request2->id], $this->requestIds());
    }

    public function testSelfServeRequests()
    {
        $nonMatchingProcess = ProcessRequest::factory()->create();
        $task1 = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'self_service_groups' => [
                'users' => ['999', (string) $this->user->id],
                'groups' => ['4', '5'],
            ],
        ]);
        $request1 = $task1->processRequest;

        $group1 = Group::factory()->create();
        $task2 = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'self_service_groups' => [
                'users' => ['999', '555'],
                'groups' => ['444', (string) $group1->id],
            ],
        ]);
        $request2 = $task2->processRequest;

        $group2 = Group::factory()->create();
        $task3 = ProcessRequestToken::factory()->create([
            'is_self_service' => true,
            'self_service_groups' => [
                'users' => ['999', '555'],
                'groups' => ['444', (string) $group2->id],
            ],
        ]);
        $request3 = $task3->processRequest;

        $this->user->groups()->sync([$group1->id, $group2->id]);

        $this->assertEquals([$request1->id, $request2->id, $request3->id], $this->requestIds());
    }

    private function requestIds()
    {
        return ProcessRequest::forUser($this->user)->pluck('id')->toArray();
    }

    public function testPerformance()
    {
        $processRequestCount = 5000;

        $this->user = $loggedInUser = User::factory()->create();

        $requestStarterUser = User::factory()->create();
        $process = Process::factory()->create();
        $processCollaboration = ProcessCollaboration::factory()->create([
            'process_id' => $process->id,
        ]);

        $requestParams = [
            'process_id' => $process->id,
            'user_id' => $requestStarterUser->id,
            'process_collaboration_id' => $processCollaboration->id,
            'process_version_id' => $process->getLatestVersion()->id,
            'callable_id' => 'ProcessId',
        ];

        $requests = ProcessRequest::factory($processRequestCount)->create($requestParams);
        $route = route('api.requests.index');

        $loggedInUser->giveDirectPermission('view-all_requests');

        $start = microtime(true);
        $result = $this->apiCall('GET', $route);
        $duration = microtime(true) - $start;

        $total = $result->json()['meta']['total'];

        $this->assertEquals($processRequestCount, $total);
        $this->assertLessThan(0.1, $duration);
    }
}
