<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Http\Controllers\Api\ProcessRequestController;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 * @group process_tests
 */
class ProcessRequestsTest extends TestCase
{
    use RequestHelper;
    use WithFaker;

    public $withPermissions = true;

    const API_TEST_URL = '/requests';

    const STRUCTURE = [
        'id',
        'process_id',
        'process_collaboration_id',
        'user_id',
        'participant_id',
        'status',
        'name',
        'completed_at',
        'initiated_at',
        'created_at',
        'updated_at',
    ];
    
    /**
     * Get a list of Requests by Cases.
     */
    public function testRequestByCase()
    {
        ProcessRequest::query()->delete();
        $request = ProcessRequest::factory()->create();
        ProcessRequest::factory()->count(9)->create([
            'parent_request_id' => $request->id,
        ]);

        $url = '/requests-by-case?case_number=' . $request->case_number;
        
        $response = $this->apiCall('GET', $url);

        //Validate the header status code
        $response->assertStatus(200);

        // Verify structure
        $response->assertJsonStructure([
            'data' => ['*' => self::STRUCTURE],
            'meta',
        ]);

        // Verify count
        $this->assertEquals(10, $response->json()['meta']['total']);
    }

    /**
     * Get a list of Requests by Cases.
     */
    public function testRequestByCaseWithoutCaseNumber()
    {
        $url = '/requests-by-case';
        
        $response = $this->apiCall('GET', $url);

        //Validate the header status code
        $response->assertStatus(422);
        $this->assertEquals('The Case number field is required.', $response->json()['message']);
    }
}
