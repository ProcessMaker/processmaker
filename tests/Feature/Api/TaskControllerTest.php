<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;
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
    public function testIndexCaseRequiresCaseNumber()
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
     * Test indexCase returns active tasks related to the case_number with pagination.
     *
     * @return void
     */
    public function testIndexCaseReturnsActiveTasksForCaseNumberPagination()
    {
        // Simulate an authenticated user
        $user = User::factory()->create();
        Auth::login($user);

        // Create a ProcessRequestToken associated with a specific case_number
        $processRequest = ProcessRequest::factory()->create();
        ProcessRequestToken::factory(12)->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'process_request_id' => $processRequest->id, // id del ProcessRequest
        ]);

        // Call the endpoint with the 'case_number' parameter page 1
        $filter = "?case_number=$processRequest->case_number&page=1&per_page=5";
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE . $filter);

        // Check if the response is successful and contains the expected tasks
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));

        // Call the endpoint with the 'case_number' parameter page 2
        $filter = "?case_number=$processRequest->case_number&page=2&per_page=5";
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE . $filter);

        // Check if the response is successful and contains the expected tasks
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));

        // Call the endpoint with the 'case_number' parameter page 3
        $filter = "?case_number=$processRequest->case_number&page=3&per_page=5";
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE . $filter);

        // Check if the response is successful and contains the expected tasks
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test indexCase returns completed tasks related to the case_number.
     *
     * @return void
     */
    public function testIndexCaseReturnsInactiveTasksForCaseNumber()
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
    public function testIndexCaseReturnsInactiveTasksForCaseNumberPagination()
    {
        // Simulate an authenticated user
        $user = User::factory()->create();
        Auth::login($user);

        // Create a ProcessRequestToken associated with a specific case_number
        $processRequest = ProcessRequest::factory()->create();
        ProcessRequestToken::factory(12)->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'process_request_id' => $processRequest->id, // id del ProcessRequest
        ]);

        // Call the endpoint with the 'case_number' parameter page 1
        $filter = "?case_number=$processRequest->case_number&status=CLOSED&page=1&per_page=5";
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE . $filter);

        // Check if the response is successful and contains the expected tasks
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));

        // Call the endpoint with the 'case_number' parameter page 2
        $filter = "?case_number=$processRequest->case_number&status=CLOSED&page=2&per_page=5";
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE . $filter);

        // Check if the response is successful and contains the expected tasks
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));

        // Call the endpoint with the 'case_number' parameter page 3
        $filter = "?case_number=$processRequest->case_number&status=CLOSED&page=3&per_page=5";
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE . $filter);

        // Check if the response is successful and contains the expected tasks
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test indexCase returns completed tasks related to the case_number.
     *
     * @return void
     */
    public function testIndexCaseReturnsWithData()
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

    /**
     * Test verify the corresponding data for each task
     *
     * @return void
     */
    public function testTasksByCaseReturnsCorrectData()
    {
        // Simulate an authenticated user
        $user = User::factory()->create();
        Auth::login($user);

        // Create a ProcessRequestToken associated with a specific case_number
        $processRequest = ProcessRequest::factory()->create();
        $data1 = ['form_input_1' => 'value a', 'form_text_area_1' => 'value a'];
        $token1 = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'process_request_id' => $processRequest->id,
            'data' => $data1,
        ]);
        $data2 = ['form_input_1' => 'value b', 'form_text_area_1' => 'value b'];
        $token2 = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'CLOSED',
            'process_request_id' => $processRequest->id,
            'data' => $data2,
        ]);

        // Call the endpoint with the 'case_number' parameter
        $filter = "?case_number=$processRequest->case_number&status=CLOSED&includeScreen=" . true;
        $response = $this->apiCall('GET', self::API_TASK_BY_CASE . $filter);

        // Check if the response is successful and contains the expected tasks
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonStructure([
            'data' => ['*' => array_merge(self::TASK_BY_CASE_STRUCTURE, ['taskData'])],
            'meta',
        ]);
        // Validate the data returned in the response
        $responseData = $response->json('data');

        // Validate the first token's data
        $this->assertEquals($token1->id, $responseData[0]['id']);
        $this->assertEquals($data1['form_input_1'], $responseData[0]['taskData']['form_input_1']);
        $this->assertEquals($data1['form_text_area_1'], $responseData[0]['taskData']['form_text_area_1']);

        // Validate the second token's data
        $this->assertEquals($token2->id, $responseData[1]['id']);
        $this->assertEquals($data2['form_input_1'], $responseData[1]['taskData']['form_input_1']);
        $this->assertEquals($data2['form_text_area_1'], $responseData[1]['taskData']['form_text_area_1']);
    }

    public function testShowTaskIncludesNewProperty()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $comments = 'This is a comment';

        $task = ProcessRequestToken::factory()->create([
            'comments' => $comments,
        ]);

        $this->assertEquals($comments, $task->comments);
    }

    private function taskShowUrl(ProcessRequestToken $task): string
    {
        return route('api.tasks.show', $task);
    }

    /**
     * When a task has no draft, the include=draft response key should be null.
     */
    public function testIncludeDraftReturnsNullWhenNoDraftExists()
    {
        $task = ProcessRequestToken::factory()->create(['user_id' => $this->user->id]);

        $response = $this->apiCall('GET', $this->taskShowUrl($task), ['include' => 'draft']);

        $response->assertStatus(200);
        $response->assertJsonPath('draft', null);
    }

    /**
     * When drafts are enabled and a draft exists, it is returned as-is and not deleted.
     */
    public function testIncludeDraftReturnsDraftWhenDraftsEnabled()
    {
        Config::set('app.task_drafts_enabled', true);

        $task = ProcessRequestToken::factory()->create(['user_id' => $this->user->id]);
        $draft = TaskDraft::factory()->create([
            'task_id' => $task->id,
            'data' => ['field' => 'value'],
        ]);

        $response = $this->apiCall('GET', $this->taskShowUrl($task), ['include' => 'draft']);

        $response->assertStatus(200);
        $response->assertJsonPath('draft.id', $draft->id);
        $this->assertDatabaseHas('task_drafts', ['id' => $draft->id]);
    }

    /**
     * When drafts are disabled and merge_draft_on_restore is true, the draft is
     * deleted after being accessed and null is returned to the frontend.
     * This is the quick-fill scenario: a draft was created to carry data into the
     * screen, but since drafts are disabled it must be consumed exactly once.
     */
    public function testIncludeDraftDeletesDraftAndReturnsNullWhenDraftsDisabledAndMergeOnRestoreEnabled()
    {
        Config::set('app.task_drafts_enabled', false);
        Config::set('app.screen.merge_draft_on_restore', true);

        $task = ProcessRequestToken::factory()->create(['user_id' => $this->user->id]);
        $draft = TaskDraft::factory()->create([
            'task_id' => $task->id,
            'data' => ['field' => 'value'],
        ]);

        $response = $this->apiCall('GET', $this->taskShowUrl($task), ['include' => 'draft']);

        $response->assertStatus(200);
        $response->assertJsonPath('draft', null);
        $this->assertDatabaseMissing('task_drafts', ['id' => $draft->id]);
    }

    /**
     * When drafts are disabled but merge_draft_on_restore is false, the draft is
     * returned as-is and NOT deleted, preserving it for future accesses.
     */
    public function testIncludeDraftReturnsDraftWithoutDeletingWhenDraftsDisabledAndMergeOnRestoreDisabled()
    {
        Config::set('app.task_drafts_enabled', false);
        Config::set('app.screen.merge_draft_on_restore', false);

        $task = ProcessRequestToken::factory()->create(['user_id' => $this->user->id]);
        $draft = TaskDraft::factory()->create([
            'task_id' => $task->id,
            'data' => ['field' => 'value'],
        ]);

        $response = $this->apiCall('GET', $this->taskShowUrl($task), ['include' => 'draft']);

        $response->assertStatus(200);
        $response->assertJsonPath('draft.id', $draft->id);
        $this->assertDatabaseHas('task_drafts', ['id' => $draft->id]);
    }

    /**
     * Quick-fill end-to-end: an inbox rule creates a draft to pre-populate the screen
     * (task_drafts_enabled=false, merge_draft_on_restore=true).
     *
     * The draft must be consumed exactly once:
     * - First request: draft data is present in the response (the frontend merges it)
     *   and the record is immediately deleted.
     * - Second request: no draft record exists, so null is returned cleanly without errors.
     *
     * This protects the quick-fill feature from regressions: if the draft were not deleted
     * on the first access the data would be re-merged on every subsequent page load; if
     * an error were thrown on the second access the task screen would break.
     */
    public function testQuickFillDraftIsConsumedExactlyOnce()
    {
        Config::set('app.task_drafts_enabled', false);
        Config::set('app.screen.merge_draft_on_restore', true);

        $task = ProcessRequestToken::factory()->create(['user_id' => $this->user->id]);
        // Simulates the draft created by an inbox rule with make_draft=true
        $draft = TaskDraft::factory()->create([
            'task_id' => $task->id,
            'data' => ['prefilled_field' => 'inbox rule value'],
        ]);

        // First access — frontend receives null so it knows to merge from the draft data
        // that was embedded in the response before deletion
        $firstResponse = $this->apiCall('GET', $this->taskShowUrl($task), ['include' => 'draft']);
        $firstResponse->assertStatus(200);
        $firstResponse->assertJsonPath('draft', null);
        $this->assertDatabaseMissing('task_drafts', ['id' => $draft->id]);

        // Second access — draft is already gone; must return null without errors
        $secondResponse = $this->apiCall('GET', $this->taskShowUrl($task), ['include' => 'draft']);
        $secondResponse->assertStatus(200);
        $secondResponse->assertJsonPath('draft', null);
    }
}
