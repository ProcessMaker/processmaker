<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Bookmark;
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
        'id',
        'user_id',
        'process_id',
        'updated_at',
        'created_at',
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
        $this->assertEmpty($response);
        // Create data related with the auth user
        $user = Auth::user();
        $process = Process::factory()->create();
        ProcessLaunchpad::factory()->count(10)->create([
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
        // Call the api PUT
        $response = $this->apiCall('PUT', self::API_TEST_URL . '/' . $process->id, ['properties' => '']);
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
        // Review if the item was deleted
        $result = ProcessLaunchpad::where('id', $launchpad->id)->get()->toArray();
        $this->assertCount(0, $result);
    }

    /**
     * Test get  media
     */
    public function testGetMedia()
    {
        // Create data
        $process = Process::factory()->create();
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL .'/' . $process->id . '/media');
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertEmpty($response);
    }

    /**
     * Test get embed
     */
    public function testGetEmbed()
    {
        // Create data
        $process = Process::factory()->create();
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL .'/' . $process->id . '/embed');
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertEmpty($response);
    }
}
