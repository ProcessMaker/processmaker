<?php

namespace Tests\Feature\Processes;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\TestCase;

class ModelerTest extends TestCase
{
    public function testInflightRoute()
    {
        $user = User::factory()->admin()->create();
        $process = Process::factory()->create();

        $route = route('modeler.inflight', [
            'process' => $process->id,
            'request' => null,
        ]);

        $response = $this->actingAs($user)->get($route);

        $response->assertStatus(200);
        $response->assertViewIs('processes.modeler.inflight');
        $response->assertSee(__('Process Map'));
    }

    public function testInflightRouteWithViewPermission()
    {
        $user = User::factory()->create();
        $adminUser = User::factory()->admin()->create();
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);
        $anotherRequest = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
        ]);

        // Our user has not participated in that request, so we expect a 403.
        $route = route('modeler.inflight', [
            'process' => $process->id,
            'request' => $request->id,
        ]);
        $response = $this->actingAs($user)->get($route);
        $response->assertStatus(403);

        // Our user is an admin, so we expect a 200.
        $response = $this->actingAs($adminUser)->get($route);
        $response->assertStatus(200);

        // Our user has participated in that request, so we expect a 200.
        $anotherRoute = route('modeler.inflight', [
            'process' => $process->id,
            'request' => $anotherRequest->id,
        ]);
        $response = $this->actingAs($user)->get($anotherRoute);
        $response->assertStatus(200);
    }
}
