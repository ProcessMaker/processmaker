<?php
namespace Tests\Feature\Api;

use ProcessMaker\Models\Process;
use Tests\TestCase;
use ProcessMaker\Models\User;

/**
 * Tests routes related to processes / CRUD related methods
 *
 */
class ProcessControllerTest extends TestCase
{

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testProcessesListing()
    {
        $initialCount = Process::count();
        $user = $this->authenticateAsAdmin();
        // Create some processes
        factory(Process::class, 5)->create();
        $response = $this->actingAs($user, 'api')->json('GET', route('processes.index'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        // Verify we have a total of 5 results
        $this->assertCount($initialCount + 5, $data['data']);
        $this->assertEquals($initialCount + 5, $data['meta']['total']);
    }

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testFiltering()
    {
        $initialCount = Process::count();
        $user = $this->authenticateAsAdmin();
        // Create some processes
        factory(Process::class, 5)->create();
        $response = $this->actingAs($user, 'api')->json('GET', route('processes.index')
            . '?filter={"status":"ACTIVE"}&include=category,category.processes');
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        dump($data);
    }

    /**
     * Create an login API as an administrator user.
     *
     * @return User
     */
    private function authenticateAsAdmin(): User
    {
        $admin = factory(User::class)->create([]);
        return $admin;
    }
}
