<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RequestHelper;

    const API_TASK_BY_CASE = '/tasks-by-case';

    const TASK_BY_CASE_STRUCTURE = [
        'id',
        'element_name',
        'user_id',
        'process_id',
        'completed_at',
        'due_at',
        'process_request_id',
    ];

    /**
     * Test indexCase without case_number.
     *
     * @return void
     */
    public function test_index_case_requires_case_number()
    {
        // Simulate an authenticated user
        $user = User::factory()->create();
        Auth::login($user);

        // Call the endpoint without the 'case_number' parameter
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE);

        // Check if the response returns a 400 error due to missing 'case_number'
        $response->assertStatus(422)
                 ->assertJson(['message' => 'The Case number field is required.']);
    }

    /**
     * Test indexCase returns active tasks related to the case_number.
     *
     * @return void
     */
    public function test_index_case_returns_active_tasks_for_case_number()
    {
        // Simulate an authenticated user
        $user = User::factory()->create();
        Auth::login($user);

        // Create a ProcessRequestToken associated with a specific case_number
        $processRequest = ProcessRequest::factory()->create();
        ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'process_request_id' => $processRequest->id, // id del ProcessRequest
        ]);

        // Call the endpoint with the 'case_number' parameter
        $filter = "?case_number=$processRequest->case_number";
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE . $filter);

        // Check if the response is successful and contains the expected tasks
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonStructure([
            'data' => ['*' => self::TASK_BY_CASE_STRUCTURE],
            'meta',
        ]);
    }

    /**
     * Test indexCase returns completed tasks related to the case_number.
     *
     * @return void
     */
    public function test_index_case_returns_inactive_tasks_for_case_number()
    {
        // Simulate an authenticated user
        $user = User::factory()->create();
        Auth::login($user);

        // Create a ProcessRequestToken associated with a specific case_number
        $processRequest = ProcessRequest::factory()->create();
        ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'process_request_id' => $processRequest->id, // id del ProcessRequest
        ]);

        // Call the endpoint with the 'case_number' parameter
        $filter = "?case_number=$processRequest->case_number&status=CLOSED";
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE . $filter);

        // Check if the response is successful and contains the expected tasks
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonStructure([
            'data' => ['*' => self::TASK_BY_CASE_STRUCTURE],
            'meta',
        ]);
    }

    /**
     * Test indexCase returns completed tasks related to the case_number.
     *
     * @return void
     */
    public function test_index_cas_returns_with_data()
    {
        // Simulate an authenticated user
        $user = User::factory()->create();
        Auth::login($user);

        // Create a ProcessRequestToken associated with a specific case_number
        $processRequest = ProcessRequest::factory()->create();
        ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'process_request_id' => $processRequest->id, // id del ProcessRequest
        ]);

        // Call the endpoint with the 'case_number' parameter
        $filter = "?case_number=$processRequest->case_number&status=CLOSED&includeScreen=" . true;
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE . $filter);

        // Check if the response is successful and contains the expected tasks
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonStructure([
            'data' => ['*' => array_merge(self::TASK_BY_CASE_STRUCTURE, ['taskData'])],
            'meta',
        ]);
    }
}
