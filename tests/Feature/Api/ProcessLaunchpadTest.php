<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessLaunchpadTest extends TestCase
{
    use RequestHelper;

    const API_TEST_URL = '/process_launchpad';

    const STRUCTURE = [
        'launchpad',
        'media',
        'embed'
    ];

    /**
     * Test get process launchpad
     */
    public function testGetProcessLaunchpad()
    {
        // Create data
        $process = Process::factory()->create();
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL .'/' . $process->id);
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
        $response = $this->apiCall('GET', self::API_TEST_URL .'/' . $process->id);
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
        $values = json_encode(["icon" => "fa-user"]);
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
}
