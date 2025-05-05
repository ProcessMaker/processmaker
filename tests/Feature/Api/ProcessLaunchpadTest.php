<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessLaunchpadTest extends TestCase
{
    use RequestHelper, RefreshDatabase;

    const API_TEST_URL = '/process_launchpad';

    const STRUCTURE = [
        'launchpad',
        'media',
        'embed',
    ];

    /**
     * Test get process launchpad
     */
    public function testGetProcessLaunchpad()
    {
        // Create data
        $process = Process::factory()->create();
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $process->id);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);

        // Create data related with the auth user
        $user = Auth::user();
        $process = Process::factory()->create();
        ProcessLaunchpad::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
        ]);
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $process->id);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);
    }

    /**
     * Test store process launchpad
     */
    public function testStoreProcessLaunchpad()
    {
        // Create data
        $process = Process::factory()->create();
        ProcessLaunchpad::factory()->create([
            'process_id' => $process->id,
        ]);
        // Call the api PUT
        $values = json_encode(['icon' => 'fa-user']);
        $response = $this->apiCall('PUT', self::API_TEST_URL . '/' . $process->id, ['properties' => $values]);
        // Validate the header status code
        $response->assertStatus(200);
    }

    /**
     * Test delete process launchpad
     */
    public function testDeleteProcessLaunchpad()
    {
        // Create data
        $launchpad = ProcessLaunchpad::factory()->create();
        // Call the api DELETE
        $response = $this->apiCall('DELETE', self::API_TEST_URL . '/' . $launchpad->id);
        // Validate the header status code
        $response->assertStatus(204);
    }

    /**
     * Test get stages count per process when stages are null.
     */
    public function testGetStagesCountPerProcessWhenStagesIsNull()
    {
        // Create data
        $process = Process::factory()->create();
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $process->id);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);
        // Create data related with the auth user
        $user = Auth::user();
        $process = Process::factory()->create([
            'stages' => null,
        ]);
        ProcessLaunchpad::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
        ]);
        ProcessRequest::factory()->create();
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $process->id);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);
        $this->assertEquals(null, $response->json('stagesSummary'));
    }

    /**
     * Test get stages count per process when there are stages but no ProcessRequests.
     */
    public function testGetStagesCountPerProcessWithStagesAndNoRequests()
    {
        // Create stages for the process
        $stages = [
            ['id' => 1, 'name' => 'Stage A'],
            ['id' => 2, 'name' => 'Stage B'],
        ];
        $user = Auth::user();
        // Create a process with stages
        $process = Process::factory()->create([
            'stages' => json_encode($stages),
        ]);
        ProcessLaunchpad::factory()->create([
            'process_id' => $process->id,
        ]);

        // No ProcessRequest records are created for this test

        $stageSummary = $process->getStagesSummary(json_encode($stages));
        $expectedStagesSummary = [
            [
                'id' => 1,
                'name' => 'Stage A',
                'count' => 0, // No requests for this stage
            ],
            [
                'id' => 2,
                'name' => 'Stage B',
                'count' => 0, // No requests for this stage
            ],
        ];
        // Check if the stagesSummary matches the expected output
        $this->assertEquals($expectedStagesSummary, $stageSummary);
    }

    /**
     * Test get stages count per process.
     */
    public function testGetStagesCountPerProcess()
    {
        // Create stages for the process
        $stages = [
            ['id' => 1, 'name' => 'Stage 1', 'order' => 1],
            ['id' => 2, 'name' => 'Stage 2', 'order' => 3],
            ['id' => 3, 'name' => 'Stage 3', 'order' => 2],
        ];
        // Create a process
        $process = Process::factory()->create([
            'stages' => json_encode($stages),
        ]);
        ProcessLaunchpad::factory()->create([
            'process_id' => $process->id,
        ]);
        // Create requests associated with the process
        ProcessRequest::factory(2)->create([
            'process_id' => $process->id,
            'last_stage_id' => 1,
            'last_stage_name' => 'Stage 1',
        ]);
        ProcessRequest::factory(3)->create([
            'process_id' => $process->id,
            'last_stage_id' => 2,
            'last_stage_name' => 'Stage 2',
        ]);
        ProcessRequest::factory(5)->create([
            'process_id' => $process->id,
            'last_stage_id' => 3,
            'last_stage_name' => 'Stage 3',
        ]);

        // Call the API GET
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $process->id);
        $stageSummary = $process->getStagesSummary(json_encode($stages));
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);

        // Validate the stagesSummary in the response
        $expectedStagesSummary = [
            ['id' => 1, 'name' => 'Stage 1', 'count' => 2], // 2 requests with last_stage_id 1
            ['id' => 2, 'name' => 'Stage 2', 'count' => 3], // 1 request with last_stage_id 2
            ['id' => 3, 'name' => 'Stage 3', 'count' => 5], // No requests with last_stage_id 3
        ];
        // Check if the stagesSummary matches the expected output
        $this->assertEquals($expectedStagesSummary, $stageSummary);
    }
}
