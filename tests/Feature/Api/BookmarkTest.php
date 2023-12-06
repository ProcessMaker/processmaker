<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Bookmark;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RequestHelper;

    const API_TEST_URL = '/process_bookmarks';

    const STRUCTURE = [
        'id',
        'user_id',
        'process_id',
        'updated_at',
        'created_at',
    ];

    /**
     * Test get bookmarks
     */
    public function testGetBookmark()
    {
        // Create a fake
        $bookmark = Bookmark::factory()->count(10)->create();
        $user = User::factory()->create();
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL);
        // Validate the header status code
        $response->assertStatus(200);
    }

    /**
     * Test store bookmarks
     */
    public function testStoreBookmark()
    {
        // Create a fake
        $process = Process::factory()->create();
        // Call the api POST
        $response = $this->apiCall('POST', self::API_TEST_URL .'/'. $process->id, []);
        // Validate the header status code
        $response->assertStatus(201);
    }

    /**
     * Test delete bookmark with error 403
     */
    public function testDeleteBookmark403()
    {
        // Create a fake
        $process = Process::factory()->create();
        // Call the api DELETE
        $response = $this->apiCall('DELETE', self::API_TEST_URL .'/'. $process->id, []);
        // Validate the header status code
        $response->assertStatus(403);
    }


    /**
     * Test delete bookmark
     */
    public function testDeleteBookmark()
    {
        // Create a fake
        $process = Process::factory()->create();
        $user = User::factory()->create();
        Auth::login($user);
        // Call the api POST
        $this->apiCall('POST', self::API_TEST_URL .'/'. $process->id, []);
        // Call the api DELETE
        $response = $this->apiCall('DELETE', self::API_TEST_URL .'/'. $process->id, [
            'user_id' => $user->id
        ]);
        // Validate the header status code
        $response->assertStatus(204);
    }
}
