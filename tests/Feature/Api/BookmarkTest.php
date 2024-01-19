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
        // Create data
        Bookmark::factory()->count(10)->create();
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertEmpty($response['data']);
        // Create data related with the auth user
        $user = Auth::user();
        Bookmark::factory()->count(10)->create([
            'user_id' => $user->id,
        ]);
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response['data']);
    }

    /**
     * Test store bookmarks
     */
    public function testStoreBookmark()
    {
        // Create data
        $process = Process::factory()->create();
        // Call the api POST
        $response = $this->apiCall('POST', self::API_TEST_URL . '/' . $process->id, []);
        // Validate the header status code
        $response->assertStatus(200);
        // Tried to save the same register twice
        $user = Auth::user();
        // Call the api POST
        $response = $this->apiCall('POST', self::API_TEST_URL . '/' . $process->id, []);
        // Validate the header status code
        $response->assertStatus(200);
        // Check if is only one register per user
        $bookmark = Bookmark::where('process_id', $process->id)->where('user_id', $user->id)->get()->toArray();
        $this->assertCount(1, $bookmark);
    }

    /**
     * Test delete bookmark
     */
    public function testDeleteBookmark()
    {
        // Create data
        $bookmark = Bookmark::factory()->create();
        // Call the api DELETE
        $response = $this->apiCall('DELETE', self::API_TEST_URL . '/' . $bookmark->id);
        // Validate the header status code
        $response->assertStatus(204);
        // Review if the item was deleted
        $bookmark = Bookmark::where('id', $bookmark->id)->get()->toArray();
        $this->assertCount(0, $bookmark);
    }
}
