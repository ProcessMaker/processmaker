<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SecurityLogControllerTest extends TestCase
{
    use RequestHelper;

    public function test_index(): void
    {
        $user = User::factory()->create([
            'status' => 'ACTIVE',
            'is_administrator' => true,
        ]);

        // Create 20 records
        SecurityLog::factory()->count(20)->create([
            'event' => 'login',
            'user_id' => $user->id,
            'meta' => [
                'os' => [
                    'name' => 'OS X',
                ],
                'browser' => [
                    'name' => 'Firefox',
                ],
            ],
        ]);

        // Get 10 items (default per page)
        $response = $this->apiCall('GET', route('api.security-logs.index'));
        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');

        // Get 2 items, page 1
        $response = $this->apiCall('GET', route('api.security-logs.index'), ['per_page' => 2]);
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['total_pages' => 10]);
        $response->assertJsonFragment(['current_page' => 1]);

        // Get 2 items, page 2
        $response = $this->apiCall('GET', route('api.security-logs.index'), ['per_page' => 2, 'page' => 2]);
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['total_pages' => 10]);
        $response->assertJsonFragment(['current_page' => 2]);
    }
}
