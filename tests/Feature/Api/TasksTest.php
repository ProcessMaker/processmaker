<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Facades\ProcessMaker\RollbackProcessRequest;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group as TestGroup;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Http\Controllers\Api\TaskController;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenVersion;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ActivityActivatedNotification;
use ProcessMaker\Providers\AuthServiceProvider;
use ProcessMaker\Services\SmartExtractConfiguration;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to tokens list and show
 * the creation, update and deletion are controller by the engine
 * and should not be changed by endpoints
 */
#[TestGroup('process_tests')]
class TasksTest extends TestCase
{
    use WithFaker;
    use ResourceAssertionsTrait;
    use RequestHelper;

    protected $resource = 'tasks';

    protected $structure = [
        'id',
        'process_request_id',
        'user_id',
        'element_id',
        'element_type',
        'element_name',
        'status',
        'completed_at',
        'due_at',
        'initiated_at',
        'riskchanges_at',
        'updated_at',
        'created_at',
    ];

    /**
     * Test to get the list of tokens
     */
    public function testGetListOfTasks()
    {
        $request = ProcessRequest::factory()->create();
        // Create some tokens
        ProcessRequestToken::factory()->count(20)->create([
            'process_request_id' => $request->id,
        ]);
        // Get a page of tokens
        $route = route('api.' . $this->resource . '.index', ['per_page' => 10, 'page' => 2]);
        $response = $this->apiCall('GET', $route);
        // Verify the status
        $response->assertStatus(200);
        // Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
    }

    /**
     * Test to get the list of overdue tasks.
     */
    public function testGetListOfOverdueTasks()
    {
        $user = User::factory()->create(['is_administrator' => true]);
        $request = ProcessRequest::factory()->create();

        // Create some tokens.
        ProcessRequestToken::factory()->count(10)->create([
            'process_request_id' => $request->id,
            'user_id' => $user->id,
            'status' => 'CLOSED',
        ]);

        // Create 5 overdue tasks.
        ProcessRequestToken::factory()->overdue()->count(5)->create([
            'process_request_id' => $request->id,
            'user_id' => $user->id,
        ]);

        $route = route('api.tasks.index', [
            'user_id' => $user->id,
            'pmql' => '(status = "In Progress") AND (due < NOW)',
        ]);
        $response = $this->actingAs($user, 'api')->get($route);
        $meta = $response->json('meta');

        // Assert that we have 5 overdue tasks for the given user.
        $this->assertEquals(5, $meta['in_overdue']);

        // Create 5 overdue self service tasks.
        ProcessRequestToken::factory()->overdue()->count(5)->create([
            'process_request_id' => $request->id,
            'user_id' => $user->id,
            'is_self_service' => true,
        ]);

        $response = $this->actingAs($user, 'api')->get($route);
        $meta = $response->json('meta');

        // Assert that we still have 5 overdue tasks for the given user.
        $this->assertEquals(5, $meta['in_overdue']);
    }

    /**
     * You only see tasks that belong to you if you are not admin
     */
    public function testGetListAssignedTasks()
    {
        $user_1 = User::factory()->create();
        $user_2 = User::factory()->create();
        $this->user = $user_1;

        $request = ProcessRequest::factory()->create();
        // Create some tokens
        ProcessRequestToken::factory()->count(2)->create([
            'process_request_id' => $request->id,
            'status' => 'ACTIVE',
            'user_id' => $user_1->id,
        ]);
        ProcessRequestToken::factory()->count(3)->create([
            'process_request_id' => $request->id,
            'status' => 'ACTIVE',
            'user_id' => $user_2->id,
        ]);
        // Get a page of tokens
        // Since PR #3470, user_id is required as parameter
        $route = route('api.' . $this->resource . '.index', ['user_id' => $user_1->id]);
        $response = $this->apiCall('GET', $route);

        // should only see the user's 2 tasks
        $this->assertEquals(2, count($response->json()['data']));
    }

    public function testUserFilterIncludesOnlyActiveSelfServiceTasksAndPreservesAssignedHistory()
    {
        $eligibleUser = User::factory()->create();
        $request = ProcessRequest::factory()->create();
        $selfServiceGroups = ['users' => [(string) $eligibleUser->id], 'groups' => []];

        $activeSelfService = ProcessRequestToken::factory()->create([
            'process_id' => $request->process_id,
            'process_request_id' => $request->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'user_id' => null,
            'is_self_service' => 1,
            'self_service_groups' => $selfServiceGroups,
        ]);
        $closedSelfService = ProcessRequestToken::factory()->create([
            'process_id' => $request->process_id,
            'process_request_id' => $request->id,
            'element_type' => 'task',
            'status' => 'CLOSED',
            'user_id' => null,
            'is_self_service' => 1,
            'self_service_groups' => $selfServiceGroups,
        ]);
        $assignedClosedTask = ProcessRequestToken::factory()->create([
            'process_id' => $request->process_id,
            'process_request_id' => $request->id,
            'element_type' => 'task',
            'status' => 'CLOSED',
            'user_id' => $eligibleUser->id,
            'is_self_service' => 0,
        ]);

        $scopeQuery = ProcessRequestToken::query();
        ProcessRequestToken::scopeWhereUserAssigned($scopeQuery, $eligibleUser->id);
        $scopeIds = $scopeQuery->pluck('id')->all();
        $this->assertContains($activeSelfService->id, $scopeIds);
        $this->assertNotContains($closedSelfService->id, $scopeIds);
        $this->assertContains($assignedClosedTask->id, $scopeIds);

        $response = $this->apiCall('GET', route('api.' . $this->resource . '.index', [
            'user_id' => $eligibleUser->id,
            'per_page' => 100,
        ]));
        $response->assertStatus(200);

        $taskIds = array_column($response->json('data'), 'id');
        $this->assertContains($activeSelfService->id, $taskIds);
        $this->assertNotContains($closedSelfService->id, $taskIds);
        $this->assertContains($assignedClosedTask->id, $taskIds);
    }

    /**
     * Return all tasks if we are only requesting closed tasks and if
     * the user can view the request.
     */
    public function testGetListClosedTasks()
    {
        $this->initializePermissions();

        $user_1 = User::factory()->create();
        $user_1->giveDirectPermission('view-all_requests');
        // $user_1->refresh();
        // $this->flushSession();

        $this->user = $user_1;

        $user_2 = User::factory()->create();

        $request = ProcessRequest::factory()->create();
        // Create some closed tasks
        ProcessRequestToken::factory()->count(3)->create([
            'process_request_id' => $request->id,
            'status' => 'CLOSED',
            'user_id' => $user_2->id,
        ]);
        ProcessRequestToken::factory()->count(1)->create([
            'process_request_id' => $request->id,
            'status' => 'ACTIVE',
            'user_id' => $user_2->id,
        ]);
        // Get a page of tokens
        $route = route('api.' . $this->resource . '.index', ['status' => 'CLOSED']);
        $response = $this->apiCall('GET', $route);

        // should only see the 3 closed tasks, not the active one
        $this->assertEquals(count($response->json()['data']), 3);
    }

    /**
     * Task list with include=draft must not expose another assignee's draft payload
     * to users who only have view-all_requests (list) without ProcessRequestTokenPolicy::view.
     */
    public function testTaskIndexExcludesOtherUsersDraftDataWithoutViewPermission()
    {
        $this->initializePermissions();
        config(['app.task_drafts_enabled' => true]);

        $viewer = User::factory()->create();
        $viewer->giveDirectPermission('view-all_requests');
        $this->user = $viewer;

        $assignee = User::factory()->create();
        $processRequest = ProcessRequest::factory()->create();

        $otherTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        TaskDraft::factory()->create([
            'task_id' => $otherTask->id,
            'data' => ['secret' => 'other-user-draft'],
        ]);

        $ownTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $viewer->id,
        ]);
        TaskDraft::factory()->create([
            'task_id' => $ownTask->id,
            'data' => ['mine' => 'own-draft'],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index', ['include' => 'draft']));
        $response->assertStatus(200);

        $tasks = collect($response->json('data'))->keyBy('id');

        $otherDraft = $tasks[$otherTask->id]['draft'];
        $this->assertNotNull($otherDraft);
        $this->assertArrayNotHasKey('data', $otherDraft);

        $ownDraft = $tasks[$ownTask->id]['draft'];
        $this->assertEquals('own-draft', $ownDraft['data']['mine'] ?? null);
    }

    public function testTaskIndexIncludesOwnDraftDataWhenTokenProjectionOmitsUserId()
    {
        $this->initializePermissions();
        config([
            'app.task_drafts_enabled' => true,
            'app.processmaker_optimized_tasks_enabled' => true,
        ]);

        $assignee = User::factory()->create();
        $this->user = $assignee;

        $processRequest = ProcessRequest::factory()->create();
        $task = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        TaskDraft::factory()->create([
            'task_id' => $task->id,
            'data' => ['field' => 'assignee-draft'],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index', [
            'include' => 'draft',
            'fields' => 'id,element_name,status',
        ]));

        $response->assertStatus(200);
        $tasks = collect($response->json('data'))->keyBy('id');
        $this->assertEquals('assignee-draft', $tasks[$task->id]['draft']['data']['field'] ?? null);
    }

    public function testTaskIndexIncludesDraftDataForProcessManagerWithStringManagerIds()
    {
        $this->initializePermissions();
        config(['app.task_drafts_enabled' => true]);

        $manager = User::factory()->create();
        $manager->giveDirectPermission('view-all_requests');
        $assignee = User::factory()->create();
        $this->user = $manager;

        $process = Process::factory()->create([
            'properties' => ['manager_id' => [(string) $manager->id]],
        ]);
        $processRequest = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $task = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        TaskDraft::factory()->create([
            'task_id' => $task->id,
            'data' => ['manager_visible' => 'manager-draft'],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index', ['include' => 'draft']));
        $response->assertStatus(200);

        $draft = collect($response->json('data'))->keyBy('id')[$task->id]['draft'];
        $this->assertEquals('manager-draft', $draft['data']['manager_visible'] ?? null);
    }

    /**
     * @return array<string, array{bool, string}>
     */
    public static function taskIndexRootDraftMediaRedactionProvider(): array
    {
        return [
            'legacy' => [false, 'legacy-draft-media'],
            'optimized' => [true, 'optimized-draft-media'],
        ];
    }

    #[DataProvider('taskIndexRootDraftMediaRedactionProvider')]
    public function testTaskIndexDraftMediaDoesNotExposeOtherUsersDraftData(
        bool $optimizedIndex,
        string $secret,
    ): void {
        $this->initializePermissions();
        config([
            'app.task_drafts_enabled' => true,
            'app.processmaker_optimized_tasks_enabled' => $optimizedIndex,
        ]);

        $viewer = User::factory()->create();
        $viewer->giveDirectPermission('view-all_requests');
        $this->user = $viewer;
        $assignee = User::factory()->create();

        $processRequest = ProcessRequest::factory()->create();
        $otherTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        TaskDraft::factory()->create([
            'task_id' => $otherTask->id,
            'data' => ['secret' => $secret],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index', ['include' => 'draft.media']));
        $response->assertStatus(200);

        $draft = collect($response->json('data'))->keyBy('id')[$otherTask->id]['draft'] ?? null;
        $this->assertNotNull($draft);
        $this->assertArrayNotHasKey('data', $draft);
    }

    public function testTaskIndexNestedDraftIncludeWithDraftsDisabledDoesNotDeleteOwnParentDraft()
    {
        $this->initializePermissions();
        config([
            'app.task_drafts_enabled' => false,
            'app.processmaker_optimized_tasks_enabled' => true,
        ]);

        $assignee = User::factory()->create();
        $this->user = $assignee;
        $processRequest = ProcessRequest::factory()->create();
        $parentTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => User::factory()->create()->id,
        ]);
        $parentDraft = TaskDraft::factory()->create([
            'task_id' => $parentTask->id,
            'data' => ['parent' => 'must-survive-nested-include'],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index', [
            'include' => 'processRequest.tokens.draft',
        ]));
        $response->assertStatus(200);

        $this->assertDatabaseHas('task_drafts', [
            'id' => $parentDraft->id,
            'task_id' => $parentTask->id,
        ]);
        $row = collect($response->json('data'))->keyBy('id')[$parentTask->id];
        $this->assertArrayNotHasKey('draft', $row);
        $nestedDraft = collect($row['process_request']['tokens'] ?? [])
            ->firstWhere('id', $parentTask->id)['draft'] ?? null;
        $this->assertNull($nestedDraft);
    }

    public function testTaskIndexProcessRequestsTokensDraftWithDraftsDisabledDoesNotDeleteOwnParentDraft()
    {
        $this->initializePermissions();
        config([
            'app.task_drafts_enabled' => false,
            'app.processmaker_optimized_tasks_enabled' => true,
        ]);

        $assignee = User::factory()->create();
        $this->user = $assignee;
        $process = Process::factory()->create();
        $processRequest = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $parentTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        $parentDraft = TaskDraft::factory()->create([
            'task_id' => $parentTask->id,
            'data' => ['parent' => 'must-survive-process-requests'],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index', [
            'include' => 'process,process.requests.tokens.draft',
        ]));
        $response->assertStatus(200);

        $this->assertDatabaseHas('task_drafts', [
            'id' => $parentDraft->id,
            'task_id' => $parentTask->id,
        ]);
        $row = collect($response->json('data'))->keyBy('id')[$parentTask->id];
        $this->assertArrayNotHasKey('draft', $row);
    }

    /**
     * @return array<string, array{string, string, bool, string|null}>
     */
    public static function taskIndexProcessRequestTokensDraftRedactionProvider(): array
    {
        return [
            'standard' => ['processRequest.tokens.draft', 'nested-process-request-token', true, null],
            'case_insensitive' => ['processRequest.tokens.Draft', 'case-insensitive-nested', false, null],
            'wildcard_select' => ['processRequest.tokens.draft:*', 'laravel-include-wildcard', false, 'laravel-include-wildcard'],
            'trailing_colon' => ['processRequest.tokens.draft:', 'laravel-include-trailing-colon', false, null],
        ];
    }

    #[DataProvider('taskIndexProcessRequestTokensDraftRedactionProvider')]
    public function testTaskIndexProcessRequestTokensDraftDoesNotExposeOtherUsersDraftData(
        string $include,
        string $otherSecret,
        bool $createOwnDraft,
        ?string $jsonMustNotContain,
    ): void {
        $this->initializePermissions();
        config([
            'app.task_drafts_enabled' => true,
            'app.processmaker_optimized_tasks_enabled' => true,
        ]);

        $viewer = User::factory()->create();
        $viewer->giveDirectPermission('view-all_requests');
        $this->user = $viewer;
        $assignee = User::factory()->create();

        $processRequest = ProcessRequest::factory()->create();
        $otherTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        TaskDraft::factory()->create([
            'task_id' => $otherTask->id,
            'data' => ['secret' => $otherSecret],
        ]);

        $ownTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $viewer->id,
        ]);
        if ($createOwnDraft) {
            TaskDraft::factory()->create([
                'task_id' => $ownTask->id,
                'data' => ['mine' => 'own-nested-pr'],
            ]);
        }

        $response = $this->apiCall('GET', route('api.tasks.index', ['include' => $include]));
        $response->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('id', $ownTask->id);
        $tokens = collect($row['process_request']['tokens'] ?? [])->keyBy('id');

        $otherDraft = $tokens[$otherTask->id]['draft'] ?? null;
        $this->assertNotNull($otherDraft);
        $this->assertArrayNotHasKey('data', $otherDraft);

        if ($createOwnDraft) {
            $ownDraft = $tokens[$ownTask->id]['draft'] ?? null;
            $this->assertEquals('own-nested-pr', $ownDraft['data']['mine'] ?? null);
        }

        if ($jsonMustNotContain !== null) {
            $this->assertStringNotContainsString($jsonMustNotContain, json_encode($response->json()));
        }
    }

    public function testTaskIndexProcessRequestsTokensDraftDoesNotExposeOtherUsersDraftData()
    {
        $this->initializePermissions();
        config([
            'app.task_drafts_enabled' => true,
            'app.processmaker_optimized_tasks_enabled' => true,
        ]);

        $viewer = User::factory()->create();
        $viewer->giveDirectPermission('view-all_requests');
        $this->user = $viewer;
        $assignee = User::factory()->create();

        $process = Process::factory()->create();
        $viewerRequest = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $otherRequest = ProcessRequest::factory()->create(['process_id' => $process->id]);

        $otherTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $otherRequest->id,
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        TaskDraft::factory()->create([
            'task_id' => $otherTask->id,
            'data' => ['secret' => 'nested-process-requests'],
        ]);

        $ownTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $viewerRequest->id,
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'user_id' => $viewer->id,
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index', [
            'include' => 'process.requests.tokens.draft',
        ]));
        $response->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('id', $ownTask->id);
        $otherDraft = collect($row['process']['requests'] ?? [])
            ->flatMap(fn ($request) => $request['tokens'] ?? [])
            ->firstWhere('id', $otherTask->id)['draft'] ?? null;

        $this->assertNotNull($otherDraft);
        $this->assertArrayNotHasKey('data', $otherDraft);
    }

    public function testLegacyTaskIndexWithoutIncludeDoesNotEmbedDraftRelation()
    {
        $this->initializePermissions();
        config([
            'app.task_drafts_enabled' => true,
            'app.processmaker_optimized_tasks_enabled' => false,
        ]);

        $assignee = User::factory()->create();
        $this->user = $assignee;
        $processRequest = ProcessRequest::factory()->create();
        $task = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        TaskDraft::factory()->create([
            'task_id' => $task->id,
            'data' => ['leaked' => 'legacy-index'],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index'));
        $response->assertStatus(200);

        $row = collect($response->json('data'))->keyBy('id')[$task->id];
        $this->assertArrayNotHasKey('draft', $row);
    }

    public function testTaskIndexNestedDraftWithDraftsDisabledDoesNotDeleteOtherAssigneeDraftForManager()
    {
        $this->initializePermissions();
        config([
            'app.task_drafts_enabled' => false,
            'app.processmaker_optimized_tasks_enabled' => true,
        ]);

        $manager = User::factory()->create();
        $assignee = User::factory()->create();
        $this->user = $manager;

        $process = Process::factory()->create([
            'properties' => ['manager_id' => [$manager->id]],
        ]);
        $processRequest = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $otherTask = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        $managerTask = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $manager->id,
        ]);
        $otherDraft = TaskDraft::factory()->create([
            'task_id' => $otherTask->id,
            'data' => ['secret' => 'manager-nested-must-not-delete'],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index', [
            'include' => 'processRequest.tokens.draft',
        ]));
        $response->assertStatus(200);

        $this->assertDatabaseHas('task_drafts', [
            'id' => $otherDraft->id,
            'task_id' => $otherTask->id,
        ]);
        $row = collect($response->json('data'))->firstWhere('id', $managerTask->id);
        $nested = collect($row['process_request']['tokens'] ?? [])->keyBy('id')[$otherTask->id]['draft'] ?? null;
        $this->assertNull($nested);
    }

    public function testTaskIndexRootDraftWithDraftsDisabledDoesNotDeleteOtherAssigneeDraftForManager()
    {
        $this->initializePermissions();
        config([
            'app.task_drafts_enabled' => false,
            'app.processmaker_optimized_tasks_enabled' => true,
        ]);

        $manager = User::factory()->create();
        $manager->giveDirectPermission('view-all_requests');
        $assignee = User::factory()->create();
        $this->user = $manager;

        $process = Process::factory()->create([
            'properties' => ['manager_id' => [$manager->id]],
        ]);
        $processRequest = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $assigneeTask = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        $assigneeDraft = TaskDraft::factory()->create([
            'task_id' => $assigneeTask->id,
            'data' => ['secret' => 'manager-root-must-not-consume'],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index', ['include' => 'draft']));
        $response->assertStatus(200);

        $this->assertDatabaseHas('task_drafts', [
            'id' => $assigneeDraft->id,
            'task_id' => $assigneeTask->id,
        ]);
        $row = collect($response->json('data'))->keyBy('id')[$assigneeTask->id];
        $this->assertNull($row['draft'] ?? null);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function taskIndexDraftsDisabledRootIncludePreservesOtherDraftProvider(): array
    {
        return [
            'draft' => ['draft', 'must-survive'],
            'draft_media' => ['draft.media', 'must-survive-media'],
        ];
    }

    #[DataProvider('taskIndexDraftsDisabledRootIncludePreservesOtherDraftProvider')]
    public function testTaskIndexWithDraftsDisabledDoesNotDeleteAnotherUsersDraft(
        string $include,
        string $secret,
    ): void {
        $this->initializePermissions();
        config([
            'app.task_drafts_enabled' => false,
            'app.processmaker_optimized_tasks_enabled' => true,
        ]);

        $viewer = User::factory()->create();
        $viewer->giveDirectPermission('view-all_requests');
        $this->user = $viewer;
        $assignee = User::factory()->create();

        $processRequest = ProcessRequest::factory()->create();
        $otherTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        $draft = TaskDraft::factory()->create([
            'task_id' => $otherTask->id,
            'data' => ['secret' => $secret],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.index', ['include' => $include]));
        $response->assertStatus(200);

        $this->assertDatabaseHas('task_drafts', ['id' => $draft->id, 'task_id' => $otherTask->id]);
        $otherRow = collect($response->json('data'))->keyBy('id')[$otherTask->id];
        $this->assertNull($otherRow['draft'] ?? null);
    }

    public function testV11TaskShowOmitsDraftWithoutIncludeDespiteFastPathPreload()
    {
        $this->initializePermissions();
        config(['app.task_drafts_enabled' => true]);

        $assignee = User::factory()->create();
        $this->user = $assignee;
        $processRequest = ProcessRequest::factory()->create();
        $task = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        TaskDraft::factory()->create([
            'task_id' => $task->id,
            'data' => ['field' => 'show-fast-preload'],
        ]);

        $response = $this->apiCall('GET', route('api.1.1.tasks.show', $task->id));
        $response->assertStatus(200);
        $this->assertArrayNotHasKey('draft', $response->json());
    }

    public function testTaskShowIncludesDraftWhenRequested()
    {
        $this->initializePermissions();
        config(['app.task_drafts_enabled' => true]);

        $assignee = User::factory()->create();
        $this->user = $assignee;
        $processRequest = ProcessRequest::factory()->create();
        $task = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        TaskDraft::factory()->create([
            'task_id' => $task->id,
            'data' => ['field' => 'show-with-include'],
        ]);

        $response = $this->apiCall('GET', route('api.tasks.show', $task->id) . '?include=draft');
        $response->assertStatus(200);
        $this->assertEquals('show-with-include', $response->json('draft.data.field'));
    }

    public function testV11TaskShowIgnoresPlantedDraftObjectsInRequestData()
    {
        $this->initializePermissions();
        config(['app.task_drafts_enabled' => true]);

        $assignee = User::factory()->create();
        $this->user = $assignee;
        $otherAssignee = User::factory()->create();

        $processRequest = ProcessRequest::factory()->create();
        $ownTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $assignee->id,
        ]);
        $otherTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
            'user_id' => $otherAssignee->id,
        ]);
        $otherDraft = TaskDraft::factory()->create([
            'task_id' => $otherTask->id,
            'data' => ['secret' => 'request-data-plant'],
        ]);

        $processRequest->update([
            'data' => [
                'draft_trap' => [
                    'id' => $ownTask->id,
                    'user_id' => $assignee->id,
                    'draft' => [
                        'id' => $otherDraft->id,
                        'task_id' => $ownTask->id,
                    ],
                ],
            ],
        ]);

        $response = $this->apiCall(
            'GET',
            route('api.1.1.tasks.show', $ownTask->id) . '?include=draft,requestData'
        );
        $response->assertStatus(200);

        $json = json_encode($response->json());
        $this->assertStringNotContainsString('request-data-plant', $json);
        $this->assertDatabaseHas('task_drafts', ['id' => $otherDraft->id]);
    }

    /**
     * You only see non system type tasks.
     */
    public function testGetListNonSystemTasks()
    {
        $user_1 = User::factory()->create();
        $user_2 = User::factory()->create();

        $process = Process::factory()->create();
        $category = ProcessCategory::factory()->create(['status' => 'ACTIVE', 'is_system' => true]);
        $systemProcess = Process::factory()->create(['process_category_id' => $category->id]);
        // Create some tokens
        ProcessRequestToken::factory()->count(2)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'user_id' => $user_1->id,
        ]);
        ProcessRequestToken::factory()->count(3)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'user_id' => $user_2->id,
        ]);
        ProcessRequestToken::factory()->count(1)->create([
            'process_id' => $systemProcess->id,
            'status' => 'ACTIVE',
            'user_id' => $user_1->id,
        ]);

        // Get a page of tokens
        // Since PR #4189, non_system = true is a default parameter
        $route = route('api.' . $this->resource . '.index', ['user_id' => $user_1->id, 'non_system' => true]);
        $response = $this->apiCall('GET', $route);

        // should only see the user's 2 tasks
        $this->assertEquals(2, count($response->json()['data']));
    }

    /**
     * Test to verify that the list dates are in the correct format (yyyy-mm-dd H:i+GMT)
     */
    public function testTaskListDates()
    {
        $name = 'testTaskTimezone';
        $request = ProcessRequest::factory()->create(['name' => $name]);
        // Create some tokens
        $newEntity = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_request_id' => $request->id,
        ]);
        $route = route('api.' . $this->resource . '.index', []);
        $response = $this->apiCall('GET', $route);

        $this->assertEquals(
            $newEntity->created_at->format('c'),
            $response->getData()->data[0]->created_at
        );

        $this->assertEquals(
            $newEntity->updated_at->format('c'),
            $response->getData()->data[0]->updated_at
        );

        $this->assertEquals(
            $newEntity->due_at->format('c'),
            $response->getData()->data[0]->due_at
        );
    }

    /**
     * Test the filtering getting active tokens
     */
    public function testFilteringGetActiveTasks()
    {
        $request = ProcessRequest::factory()->create();
        // Create some tokens
        ProcessRequestToken::factory()->count(10)->create([
            'status' => 'ACTIVE',
            'process_request_id' => $request->id,
        ]);
        ProcessRequestToken::factory()->count(10)->create([
            'status' => 'CLOSED',
            'process_request_id' => $request->id,
        ]);

        // Get active tokens
        $route = route('api.' . $this->resource . '.index', ['per_page' => 10, 'status' => 'ACTIVE']);
        $response = $this->apiCall('GET', $route);
        // Verify the status
        $response->assertStatus(200);
        // Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
    }

    /**
     * Test that we filter only for human tasks
     */
    public function testFilteringGetOnlyHumanTasks()
    {
        $request = ProcessRequest::factory()->create();

        // Create some startEvent tokens
        ProcessRequestToken::factory()->count(5)->create([
            'element_type' => 'startEvent',
            'process_request_id' => $request->id,
        ]);

        // Create some scriptTask tokens
        ProcessRequestToken::factory()->count(5)->create([
            'element_type' => 'scriptTask',
            'process_request_id' => $request->id,
        ]);

        // Create some task tokens
        ProcessRequestToken::factory()->count(5)->create([
            'element_type' => 'task',
            'process_request_id' => $request->id,
        ]);

        // Get tasks
        $route = route('api.' . $this->resource . '.index', ['per_page' => 100]);
        $response = $this->apiCall('GET', $route);

        // Verify the status
        $response->assertStatus(200);

        // Verify the element types
        $types = collect($response->json()['data'])->pluck('element_type')->unique()->toArray();
        $this->assertEquals($types, ['task']);
    }

    /**
     * Test filtering with string vs number
     */
    public function testFilteringWithStringOrNumber()
    {
        $request = ProcessRequest::factory()->create();
        $process = $request->process;
        $task = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
            'process_request_id' => $request->id,
            'process_id' => $process->id,
            'element_name' => 'foobar',
        ]);
        $anotherTask = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
            'element_name' => 'barbaz',
        ]);

        $route = route('api.' . $this->resource . '.index', ['process_id' => $process->id]);

        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
        $this->assertEquals($process->id, $response->json()['data'][0]['process_id']);

        $route = route('api.' . $this->resource . '.index', ['element_name' => 'foo%']);

        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
        $this->assertEquals($process->id, $response->json()['data'][0]['process_id']);
    }

    /**
     * Test list of tokens sorting by completed_at
     */
    public function testSorting()
    {
        $request = ProcessRequest::factory()->create();
        // Create some tokens
        ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'completed_at' => null,
            'process_request_id' => $request->id,
        ]);
        ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'completed_at' => Carbon::now(),
            'process_request_id' => $request->id,
        ]);

        // List sorted by completed_at returns as first row {"completed_at": null}
        $route = route('api.' . $this->resource . '.index', ['order_by' => 'completed_at', 'order_direction' => 'asc']);
        $response = $this->apiCall('GET', $route);
        // Verify the status
        $response->assertStatus(200);
        // Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        // Verify the first row
        $firstRow = $response->json('data')[0];
        $this->assertArraySubset(['completed_at' => null], $firstRow);
    }

    public function testSortByRequestName()
    {
        // $request = ProcessRequest::factory()->create();

        ProcessRequestToken::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'completed_at' => Carbon::now(),
        ]);

        $tasks = ProcessRequestToken::all()->pluck('process_request_id')->sort();

        // Order by process_request_id
        $route = route('api.' . $this->resource . '.index', [
            'order_by' => 'process_request_id',
            'order_direction' =>'asc',
        ]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
        $firstRow = $response->json('data')[0];
        $this->assertEquals($tasks->first(), $firstRow['process_request_id']);

        // Order by the request name (id + name)
        $route = route('api.' . $this->resource . '.index', [
            'order_by' => 'process_requests.id,process_requests.name',
            'order_direction' =>'desc',
        ]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
        $firstRow = $response->json('data')[0];
        $this->assertEquals($tasks->last(), $firstRow['process_request_id']);
    }

    public function testSortByProcessRequestColumnsAndJsonData()
    {
        $firstRequest = ProcessRequest::factory()->create([
            'name' => 'First request',
            'case_title' => 'First case',
            'data' => ['priority' => 'low'],
        ]);
        $secondRequest = ProcessRequest::factory()->create([
            'name' => 'Second request',
            'case_title' => 'Second case',
            'data' => ['priority' => 'high'],
        ]);

        ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_id' => $firstRequest->process_id,
            'process_request_id' => $firstRequest->id,
        ]);
        ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'process_id' => $secondRequest->process_id,
            'process_request_id' => $secondRequest->id,
        ]);

        $response = $this->apiCall('GET', route('api.' . $this->resource . '.index', [
            'order_by' => 'process_requests.case_title',
            'order_direction' => 'desc',
        ]));
        $response->assertStatus(200);
        $this->assertEquals(
            $secondRequest->id,
            $response->json('data.0.process_request_id')
        );

        $response = $this->apiCall('GET', route('api.' . $this->resource . '.index', [
            'order_by' => 'data.priority',
            'order_direction' => 'asc',
        ]));
        $response->assertStatus(200);
        $this->assertEquals(
            $secondRequest->id,
            $response->json('data.0.process_request_id')
        );
    }

    public function testInvalidOrderingDoesNotReachSql()
    {
        $response = $this->apiCall('GET', route('api.' . $this->resource . '.index', [
            'order_by' => 'process_requests.name) desc, sleep(5) --',
            'order_direction' => 'invalid',
        ]));

        $response->assertStatus(200);
    }

    /**
     * Test pagination of tokens list
     */
    public function testPagination()
    {
        $request = ProcessRequest::factory()->create();
        // Number of tokens in the tables at the moment of starting the test
        $initialRows = ProcessRequestToken::all()->count();

        // Number of rows to be created for the test
        $rowsToAdd = 10;

        // Now we create the specified number of tokens
        ProcessRequestToken::factory()->count($rowsToAdd)->create([
            'user_id' => $this->user->id,
            'process_request_id' => $request->id,
        ]);

        // Get the second page, should have 5 items
        $perPage = 5;
        $page = 2;
        $response = $this->apiCall('GET', route('api.' . $this->resource . '.index', ['per_page' => $perPage, 'page' => $page]));
        $response->assertJsonCount($perPage, 'data');
        // Verify the meta information
        $this->assertArraySubset(
            [
                'total' => $initialRows + $rowsToAdd,
                'count' => $perPage,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil(($initialRows + $rowsToAdd) / $perPage),
            ],
            $response->json('meta')
        );
    }

    /**
     * Test show a token
     */
    public function testShowTask()
    {
        $request = ProcessRequest::factory()->create();
        // Create a new process without category
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
        ]);

        // Test that is correctly displayed
        $route = route('api.' . $this->resource . '.show', [$token->id]);
        $response = $this->apiCall('GET', $route);
        // Check the status
        $response->assertStatus(200);
        // Check the structure
        $response->assertJsonStructure($this->structure);
    }

    /**
     * Test get a token including user child.
     */
    public function testShowTaskWithUser()
    {
        $request = ProcessRequest::factory()->create();
        // Create a new process without category
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
        ]);

        // Test that is correctly displayed
        $route = route('api.' . $this->resource . '.show', [$token->id, 'include' => 'user,definition']);
        $response = $this->apiCall('GET', $route);
        // Check the status
        $this->assertStatus(200, $response);
        // Check the structure
        $response->assertJsonStructure($this->structure);
        $response->assertJsonStructure(['user' => ['id', 'email'], 'definition' => []]);
    }

    public function testShowTaskWithParentRequest()
    {
        $this->user = User::factory()->create();
        $parent = ProcessRequest::factory()->create();
        $request = ProcessRequest::factory()->create([
            'parent_request_id' => $parent->id,
        ]);

        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'user_id' => $this->user->id,
        ]);

        // Test that is correctly displayed
        $route = route('api.' . $this->resource . '.show', [$token->id, 'include' => 'processRequestParent']);
        $response = $this->apiCall('GET', $route);
        // Check the status
        $this->assertStatus(200, $response);
        // Check the structure
        $json = $response->json();
        $this->assertFalse($json['can_view_parent_request']);

        $parent->user_id = $this->user->id;
        $parent->save();

        $response = $this->apiCall('GET', $route);
        $json = $response->json();
        $this->assertTrue($json['can_view_parent_request']);
    }

    public function testUpdateTask()
    {
        $this->user = User::factory()->create(); // normal user
        $request = ProcessRequest::factory()->create();
        $token = ProcessRequestToken::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
        ]);
        $params = ['status' => 'COMPLETED', 'data' => ['foo' => '<p>bar</p>']];
        WorkflowManager::shouldReceive('completeTask')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), ['foo' => 'bar']);
        $response = $this->apiCall('PUT', '/tasks/' . $token->id, $params);
        $this->assertStatus(200, $response);
    }

    public function testUpdateTaskRichText()
    {
        // $this->user = User::factory()->create(); // normal user
        $screen = Screen::factory()->create([
            'config' => json_decode(
                file_get_contents(
                    base_path('tests/Fixtures/rich_text_screen.json')
                )
            ),
        ]);

        $bpmn = file_get_contents(base_path('tests/Fixtures/single_task_with_screen.bpmn'));
        $bpmn = str_replace('pm:screenRef="1"', 'pm:screenRef="' . $screen->id . '"', $bpmn);
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
            'user_id' => $this->user->id,
        ]);

        $route = route('api.process_events.trigger', $process);

        $response = $this->apiCall('POST', $route . '?event=node_1');
        $this->assertStatus(201, $response);
        $request = ProcessRequest::find($response->json()['id']);
        $token = $request->tokens()->where('status', 'ACTIVE')->firstOrFail();

        $params = ['status' => 'COMPLETED', 'data' => [
            'input1' => '<p>foo</p>',
            'richtext1' => '<p>bar</p>',
            'richtext2' => '<p>another</p>',
        ]];
        WorkflowManager::shouldReceive('completeTask')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any(), [
                'input1' => 'foo',
                'richtext1' => '<p>bar</p>', // do not sanitize rich text
                'richtext2' => '<p>another</p>',
            ]);
        $response = $this->apiCall('PUT', '/tasks/' . $token->id, $params);
        $this->assertStatus(200, $response);
    }

    public function testWithUserWithoutAuthorization()
    {
        // We'll test viewing a new task with someone that is not authenticated
        $request = ProcessRequest::factory()->create();

        // Create a new process without category
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
        ]);
        $url = route('api.' . $this->resource . '.show', [$token->id, 'include' => 'user,definition']);

        // The call is done without an authenticated user so it should return 401
        $response = $this->actingAs(User::factory()->create())
            ->json('GET', $url, []);
        $response->assertStatus(401);
    }

    public function testSelfServeTasks()
    {
        $this->user = $user = User::factory()->create(['status' => 'ACTIVE']);
        $otherUser = User::factory()->create(['status' => 'ACTIVE']);

        $group1 = Group::factory()->create();
        GroupMember::factory()->create([
            'member_id' => $user->id,
            'member_type' => User::class,
            'group_id' => $group1->id,
        ]);
        $group2 = Group::factory()->create();
        GroupMember::factory()->create([
            'member_id' => $user->id,
            'member_type' => User::class,
            'group_id' => $group2->id,
        ]);

        $params = [
            'status' => 'ACTIVE',
            'user_id' => null,
            'is_self_service' => true,
        ];

        $selfServiceTaskOriginal = ProcessRequestToken::factory()->create(
            array_merge($params, [
                'self_service_groups' => [(string) $group1->id],
            ])
        );
        $selfServiceTaskGroups = ProcessRequestToken::factory()->create(
            array_merge($params, [
                'self_service_groups' => [
                    'groups' => [(string) $group2->id],
                ],
            ])
        );
        $selfServiceTaskUsers = ProcessRequestToken::factory()->create(
            array_merge($params, [
                'self_service_groups' => [
                    'users' => [(string) $user->id, (string) $otherUser->id],
                ],
            ])
        );
        $selfServiceTaskOtherUser = ProcessRequestToken::factory()->create(
            array_merge($params, [
                'self_service_groups' => [
                    'users' => [(string) $otherUser->id],
                ],
            ])
        );
        $selfServiceTaskAssigned = ProcessRequestToken::factory()->create(
            array_merge($params, [
                'self_service_groups' => [
                    'users' => [(string) $group1->id],
                ],
                'user_id' => $user->id,
            ])
        );
        $regularTask = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
            'user_id' => $user->id,
        ]);

        $userId = $user->id;
        $url = route('api.tasks.index') . '?pmql=(status%20%3D%20%22Self%20Service%22)';
        $response = $this->apiCall('GET', $url);

        $expectedTaskIds = collect([
            $selfServiceTaskOriginal,
            $selfServiceTaskGroups,
            $selfServiceTaskUsers,
        ])->pluck('id');

        $actualIds = collect($response->json()['data'])->pluck('id');

        $this->assertEquals($expectedTaskIds, $actualIds);
    }

    public function testSelfServeNotifications()
    {
        Notification::fake();

        $bpmn = str_replace(
            '[self_serve_user_id]',
            $this->user->id,
            file_get_contents(__DIR__ . '/../../Fixtures/self_serve_notifications_process.bpmn')
        );
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
        ]);
        ProcessNotificationSetting::factory()->create([
            'process_id' => $process->id,
            'element_id' => 'node_3',
            'notifiable_type' => 'requester',
            'notification_type' => 'assigned',
        ]);

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, []);
        $processRequest = ProcessRequest::findOrFail($response->json()['id']);

        Notification::assertNothingSent();

        $task = $processRequest->tokens->where('status', 'ACTIVE')->first();
        $route = route('api.tasks.update', [$task->id]);
        $response = $this->apiCall('PUT', $route, ['user_id' => $this->user->id]);

        Notification::assertSentTo([$this->user], ActivityActivatedNotification::class);
    }

    public function testRollback()
    {
        $bpmn = file_get_contents(__DIR__ . '/../../Fixtures/rollback_test.bpmn');
        $bpmn = str_replace('[task_user_id]', $this->user->id, $bpmn);
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
        ]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_255',
            'assignment_id' => $this->user->id,
            'assignment_type' => 'ProcessMaker\Models\User',
        ]);
        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('node_1');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);

        $formTask = $request->tokens()->where('element_id', 'node_255')->firstOrFail();

        // Next task should fail because the rule expression variable 'foo' does not exist
        WorkflowManager::completeTask($process, $request, $formTask, ['someValue' => 123]);

        $this->assertEquals('ERROR', $request->refresh()->status);

        $errorTask = RollbackProcessRequest::getErrorTask($request);
        $processDefinitions = $process->getDefinitions();
        $newTask = RollbackProcessRequest::rollback($errorTask, $processDefinitions);

        $this->assertEquals('ACTIVE', $request->refresh()->status);
        $this->assertEquals($newTask->id, $request->tokens()->where('status', 'ACTIVE')->first()->id);

        // Set data to make the rule expression pass
        WorkflowManager::completeTask($process, $request, $newTask, ['foo' => 123]);

        // Now we have a valid task we can use to complete the request
        $ruleExpressionTask = $request->refresh()->tokens()
            ->where('element_id', 'node_272')
            ->where('status', 'ACTIVE')
            ->firstOrFail();

        WorkflowManager::completeTask($process, $request, $ruleExpressionTask, []);

        $this->assertEquals('CLOSED', $newTask->refresh()->status);
        $this->assertEquals('COMPLETED', $request->refresh()->status);
    }

    public function testAdvancedFilter()
    {
        $hitProcess = Process::factory()->create(['name' => 'foo']);
        $missProcess = Process::factory()->create(['name' => 'bar']);
        $hitRequest = ProcessRequest::factory()->create([
            'process_id' => $hitProcess->id,
        ]);
        $missRequest = ProcessRequest::factory()->create([
            'process_id' => $missProcess->id,
        ]);
        $hitTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $hitRequest->id,
        ]);
        $missTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $missRequest->id,
        ]);

        $filterString = json_encode([
            [
                'subject' => ['type' => 'Process'],
                'operator' => '=',
                'value' => $hitProcess->id,
            ],
        ]);

        $response = $this->apiCall('GET', '/tasks', ['advanced_filter' => $filterString]);
        $json = $response->json();

        $this->assertEquals($hitTask->id, $json['data'][0]['id']);
    }

    public function testAdvancedFilterRejectsUntrustedRawExpression()
    {
        $filter = json_encode([
            [
                'subject' => ['type' => 'Field', 'value' => 'due_at'],
                'operator' => '=',
                'value' => 'raw((SELECT password FROM users LIMIT 1))',
            ],
        ]);

        $response = $this->apiCall('GET', '/tasks', ['advanced_filter' => $filter]);

        $response->assertStatus(422);
    }

    public function testAdvancedFilterByProcessRequestName()
    {
        $hitProcess = Process::factory()->create(['name' => 'foo']);
        $missProcess = Process::factory()->create(['name' => 'bar']);
        $hitRequest = ProcessRequest::factory()->create([
            'process_id' => $hitProcess->id,
            'name' => $hitProcess->name,
        ]);
        $missRequest = ProcessRequest::factory()->create([
            'process_id' => $missProcess->id,
        ]);
        $hitTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $hitRequest->id,
        ]);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $missRequest->id,
        ]);

        // Filter by operator =
        $filterString = json_encode([
            [
                'subject' => ['type' => 'Field', 'value' => 'process_request.name'],
                'operator' => '=',
                'value' => $hitProcess->name,

            ],
        ]);

        $response = $this->apiCall('GET', '/tasks', ['advanced_filter' => $filterString]);
        $json = $response->json();

        $this->assertEquals($hitTask->id, $json['data'][0]['id']);
        // Filter by operator contains
        $filterString = json_encode([
            [
                'subject' => ['type' => 'Field', 'value' => 'process_request.name'],
                'operator' => 'contains',
                'value' => $hitProcess->name,

            ],
        ]);

        $response = $this->apiCall('GET', '/tasks', ['advanced_filter' => $filterString]);
        $json = $response->json();

        $this->assertEquals($hitTask->id, $json['data'][0]['id']);
    }

    public function testAdvancedStatusFilterOverridesPmqlStatus()
    {
        $user = User::factory()->create(['is_administrator' => true]);

        $activeTask = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
            'element_type' => 'task',
            'user_id' => $user->id,
            'is_self_service' => 0,
        ]);

        $completedTask = ProcessRequestToken::factory()->create([
            'status' => 'CLOSED',
            'element_type' => 'task',
            'user_id' => $user->id,
            'is_self_service' => 0,
        ]);

        $statusFilter = json_encode([
            [
                'subject' => ['type' => 'Status'],
                'operator' => '=',
                'value' => 'Completed',
            ],
        ]);

        $response = $this->actingAs($user, 'api')->get(route('api.tasks.index', [
            'pmql' => '(user_id = ' . $user->id . ') AND (status = "In Progress")',
            'advanced_filter' => $statusFilter,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $returnedIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($completedTask->id, $returnedIds);
        $this->assertNotContains($activeTask->id, $returnedIds);
    }

    public function testProcessManagerAdvancedStatusFilterCanReturnCompletedTasks()
    {
        $manager = User::factory()->create();
        $process = Process::factory()->create([
            'properties' => ['manager_id' => $manager->id],
        ]);
        $unmanagedProcess = Process::factory()->create();
        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);

        $activeTask = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
            'element_type' => 'task',
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'is_self_service' => 0,
        ]);

        $completedTask = ProcessRequestToken::factory()->create([
            'status' => 'CLOSED',
            'element_type' => 'task',
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'is_self_service' => 0,
        ]);

        $unmanagedCompletedTask = ProcessRequestToken::factory()->create([
            'status' => 'CLOSED',
            'element_type' => 'task',
            'process_id' => $unmanagedProcess->id,
            'is_self_service' => 0,
        ]);

        $statusFilter = json_encode([
            [
                'subject' => ['type' => 'Status'],
                'operator' => '=',
                'value' => 'Completed',
            ],
        ]);

        $response = $this->actingAs($manager, 'api')->get(route('api.tasks.index', [
            'processesIManage' => 'true',
            'advanced_filter' => $statusFilter,
        ]));

        $response->assertStatus(200);
        $returnedIds = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertContains($completedTask->id, $returnedIds);
        $this->assertNotContains($activeTask->id, $returnedIds);
        $this->assertNotContains($unmanagedCompletedTask->id, $returnedIds);
    }

    private function createManagedProcessForVisibilityTest(User $manager, bool $system = false): Process
    {
        $category = ProcessCategory::factory()->create(['is_system' => $system]);
        $process = Process::factory()->create([
            'user_id' => $manager->id,
            'process_category_id' => $category->id,
            'properties' => ['manager_id' => [$manager->id]],
        ]);
        $process->categories()->sync([$category->id]);

        return $process;
    }

    private function createManagedTaskForVisibilityTest(Process $process, array $attributes = []): ProcessRequestToken
    {
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $this->user->id,
        ]);

        return ProcessRequestToken::factory()->create(array_merge([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'user_id' => $this->user->id,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'is_self_service' => 0,
        ], $attributes));
    }

    private function setManagerVisibilityHitl(bool $enabled): void
    {
        $configuration = Mockery::mock(SmartExtractConfiguration::class);
        $configuration->shouldReceive('hitlEnabled')->andReturn($enabled);
        $this->app->instance(SmartExtractConfiguration::class, $configuration);
    }

    public function testManagedTaskStatusesAndMultipleProcesses()
    {
        $this->setManagerVisibilityHitl(false);
        $this->user = User::factory()->create(['is_administrator' => true]);
        $first = $this->createManagedProcessForVisibilityTest($this->user);
        $second = $this->createManagedProcessForVisibilityTest($this->user);
        $active = $this->createManagedTaskForVisibilityTest($first);
        $closed = $this->createManagedTaskForVisibilityTest($first, ['status' => 'CLOSED']);
        $completed = $this->createManagedTaskForVisibilityTest($second, ['status' => 'COMPLETED']);
        $selfService = $this->createManagedTaskForVisibilityTest($second, ['user_id' => null, 'is_self_service' => 1]);
        $this->createManagedTaskForVisibilityTest($second, ['is_self_service' => 1]);
        $this->createManagedTaskForVisibilityTest($second, ['user_id' => null, 'is_self_service' => 1, 'status' => 'CLOSED']);

        $parameters = ['processesIManage' => 'true', 'non_system' => 'true', 'per_page' => 100];
        $cases = [
            '(status = "Self Service" or status = "In Progress" or status = "Completed")' => [$active->id, $closed->id, $completed->id, $selfService->id],
            '(status = "Self Service")' => [$selfService->id],
            '(status = "In Progress")' => [$active->id],
            '(status = "Completed")' => [$closed->id, $completed->id],
        ];
        foreach ($cases as $pmql => $expected) {
            $response = $this->apiCall('GET', '/tasks', $parameters + ['pmql' => $pmql]);
            $response->assertStatus(200);
            $this->assertEqualsCanonicalizing($expected, collect($response->json('data'))->pluck('id')->all());
            $this->assertEquals(count($expected), $response->json('meta.total'));
        }

        foreach ([['Self Service'], ['Self Service', 'Completed']] as $statuses) {
            $response = $this->apiCall('GET', '/tasks', $parameters + [
                'advanced_filter' => json_encode([[
                    'subject' => ['type' => 'Status'], 'operator' => '=', 'value' => $statuses,
                ]]),
            ]);
            $response->assertStatus(200);
            $expected = count($statuses) === 1 ? [$selfService->id] : [$selfService->id, $closed->id, $completed->id];
            $this->assertEqualsCanonicalizing($expected, collect($response->json('data'))->pluck('id')->all());
        }
    }

    public function testManagerOfOneProcessDoesNotSeeOtherProcesses()
    {
        $this->setManagerVisibilityHitl(false);
        $this->user = User::factory()->create(['is_administrator' => true]);
        $managed = $this->createManagedProcessForVisibilityTest($this->user);
        $unmanaged = $this->createManagedProcessForVisibilityTest(User::factory()->create());
        $task = $this->createManagedTaskForVisibilityTest($managed);
        $this->createManagedTaskForVisibilityTest($unmanaged);

        $response = $this->apiCall('GET', '/tasks', ['processesIManage' => 'true', 'non_system' => 'true']);
        $response->assertStatus(200);
        $this->assertEquals([$task->id], collect($response->json('data'))->pluck('id')->all());
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function testManagerWithoutManagedProcessesGetsEmptyResults()
    {
        $this->setManagerVisibilityHitl(false);
        $otherManager = User::factory()->create();
        $process = $this->createManagedProcessForVisibilityTest($otherManager);
        $this->createManagedTaskForVisibilityTest($process);
        $this->user = User::factory()->create(['is_administrator' => true]);

        $response = $this->apiCall('GET', '/tasks', [
            'processesIManage' => 'true', 'non_system' => 'true',
            'pmql' => '(status = "Self Service" or status = "In Progress" or status = "Completed")',
        ]);
        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'));
        $this->assertEquals(0, $response->json('meta.total'));
    }

    public function testManagerVisibilityUsesNonSystemCategoryAndPublishedNonDeletedProcess()
    {
        $this->setManagerVisibilityHitl(false);
        $this->user = User::factory()->create(['is_administrator' => true]);
        $visible = $this->createManagedProcessForVisibilityTest($this->user);
        $mixed = $this->createManagedProcessForVisibilityTest($this->user);
        $system = $this->createManagedProcessForVisibilityTest($this->user, true);
        $deleted = $this->createManagedProcessForVisibilityTest($this->user);
        $unpublished = $this->createManagedProcessForVisibilityTest($this->user);
        $noCategory = $this->createManagedProcessForVisibilityTest($this->user);
        $inactive = $this->createManagedProcessForVisibilityTest($this->user);
        $mixed->categories()->attach(ProcessCategory::factory()->create(['is_system' => true])->id);
        $noCategory->categories()->detach();

        $visibleTask = $this->createManagedTaskForVisibilityTest($visible);
        $mixedTask = $this->createManagedTaskForVisibilityTest($mixed);
        foreach ([$system, $deleted, $unpublished, $noCategory, $inactive] as $process) {
            $this->createManagedTaskForVisibilityTest($process);
        }
        $connection = DB::connection('processmaker');
        $connection->table('processes')->where('id', $deleted->id)->update(['deleted_at' => now()]);
        $connection->table('processes')->where('id', $inactive->id)->update(['status' => 'INACTIVE']);
        $connection->table('process_versions')->where('process_id', $unpublished->id)->update(['draft' => true]);

        $response = $this->apiCall('GET', '/tasks', [
            'processesIManage' => 'true', 'non_system' => 'true', 'per_page' => 100,
        ]);
        $response->assertStatus(200);
        $this->assertEqualsCanonicalizing([$visibleTask->id, $mixedTask->id], collect($response->json('data'))->pluck('id')->all());
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function testManyPublishedProcessVersionsDoNotChangeManagerResults()
    {
        $this->setManagerVisibilityHitl(false);
        $this->user = User::factory()->create(['is_administrator' => true]);
        $process = $this->createManagedProcessForVisibilityTest($this->user);
        $task = $this->createManagedTaskForVisibilityTest($process);
        $versions = DB::connection('processmaker')->table('process_versions');
        $version = (array) $versions->where('process_id', $process->id)->where('draft', false)->first();
        unset($version['id']);
        for ($i = 0; $i < 230; $i++) {
            DB::connection('processmaker')->table('process_versions')->insert($version);
        }
        $this->assertGreaterThanOrEqual(231, DB::connection('processmaker')->table('process_versions')
            ->where('process_id', $process->id)->where('draft', false)->count());

        $response = $this->apiCall('GET', '/tasks', ['processesIManage' => 'true', 'non_system' => 'true']);
        $response->assertStatus(200);
        $this->assertEquals([$task->id], collect($response->json('data'))->pluck('id')->all());
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function testManagerCountDoesNotCorrelateProcessChecksOrSelfServiceSubquery()
    {
        $this->setManagerVisibilityHitl(false);
        $this->user = User::factory()->create(['is_administrator' => true]);
        $process = $this->createManagedProcessForVisibilityTest($this->user);
        $this->createManagedTaskForVisibilityTest($process, ['is_self_service' => 1, 'user_id' => null]);
        $filters = [
            ['pmql' => '(status = "Self Service" or status = "In Progress" or status = "Completed")'],
            ['advanced_filter' => json_encode([['subject' => ['type' => 'Status'], 'operator' => '=', 'value' => ['Self Service', 'Completed']]])],
        ];

        foreach ($filters as $filter) {
            $connection = DB::connection('processmaker');
            $connection->flushQueryLog();
            $connection->enableQueryLog();
            try {
                $response = $this->apiCall('GET', '/tasks', [
                    'processesIManage' => 'true', 'non_system' => 'true',
                ] + $filter);
                $queries = $connection->getQueryLog();
            } finally {
                $connection->disableQueryLog();
            }
            $response->assertStatus(200);
            $counts = collect($queries)->filter(function ($query) {
                $sql = strtolower($query['query']);

                return str_contains($sql, 'count(*)') && str_contains($sql, 'process_request_tokens')
                    && str_contains($sql, 'element_type');
            })->values();
            $this->assertCount(1, $counts);
            $sql = str_replace(['`', '"'], '', strtolower($counts[0]['query']));
            $this->assertStringNotContainsString('exists (select', $sql);
            $this->assertSame(1, substr_count($sql, 'from process_request_tokens'));
            $this->assertStringNotContainsString('process_versions', $sql);
            $this->assertStringNotContainsString('from processes', $sql);
        }
    }

    public function testManagerVisibilityPreservesManualReviewExceptionAndNonSystemOption()
    {
        $this->user = User::factory()->create(['is_administrator' => true]);
        $process = $this->createManagedProcessForVisibilityTest($this->user, true);
        $regular = $this->createManagedTaskForVisibilityTest($process);
        $review = $this->createManagedTaskForVisibilityTest($process, ['element_name' => 'Manual Document Review']);

        $this->setManagerVisibilityHitl(true);
        $response = $this->apiCall('GET', '/tasks', ['processesIManage' => 'true', 'non_system' => 'true']);
        $response->assertStatus(200);
        $this->assertEqualsCanonicalizing([$review->id], collect($response->json('data'))->pluck('id')->all());

        $this->setManagerVisibilityHitl(false);
        $response = $this->apiCall('GET', '/tasks', ['processesIManage' => 'true', 'non_system' => 'false']);
        $response->assertStatus(200);
        $this->assertEqualsCanonicalizing([$regular->id, $review->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function testProcessManagerAdvancedStatusFilterCanReturnInProgressCompletedAndSelfServiceTasks()
    {
        $manager = User::factory()->create();
        $process = Process::factory()->create([
            'properties' => ['manager_id' => $manager->id],
        ]);

        $activeTask = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
            'element_type' => 'task',
            'process_id' => $process->id,
            'is_self_service' => 0,
        ]);

        $completedTask = ProcessRequestToken::factory()->create([
            'status' => 'CLOSED',
            'element_type' => 'task',
            'process_id' => $process->id,
            'is_self_service' => 0,
        ]);

        $selfServiceTask = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
            'element_type' => 'task',
            'process_id' => $process->id,
            'user_id' => null,
            'is_self_service' => 1,
        ]);

        $statusFilter = json_encode([
            [
                'subject' => ['type' => 'Status'],
                'operator' => '=',
                'value' => ['In Progress', 'Completed', 'Self Service'],
            ],
        ]);

        $response = $this->actingAs($manager, 'api')->get(route('api.tasks.index', [
            'processesIManage' => 'true',
            'advanced_filter' => $statusFilter,
        ]));

        $response->assertStatus(200);
        $returnedIds = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertContains($activeTask->id, $returnedIds);
        $this->assertContains($completedTask->id, $returnedIds);
        $this->assertContains($selfServiceTask->id, $returnedIds);
    }

    public function testPmqlStatusPreservedWhenNoAdvancedStatusFilter()
    {
        $user = User::factory()->create(['is_administrator' => true]);

        $activeTask = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
            'element_type' => 'task',
            'user_id' => $user->id,
            'is_self_service' => 0,
        ]);

        $completedTask = ProcessRequestToken::factory()->create([
            'status' => 'CLOSED',
            'element_type' => 'task',
            'user_id' => $user->id,
            'is_self_service' => 0,
        ]);

        $response = $this->actingAs($user, 'api')->get(route('api.tasks.index', [
            'pmql' => '(user_id = ' . $user->id . ') AND (status = "In Progress")',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $returnedIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($activeTask->id, $returnedIds);
        $this->assertNotContains($completedTask->id, $returnedIds);
    }

    public function testSelfServiceFilterOverridesPmqlStatusAndUserId()
    {
        $user = User::factory()->create(['is_administrator' => true]);
        $group = Group::factory()->create();

        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => $user->id,
            'member_type' => User::class,
        ]);

        $selfServiceTask = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
            'element_type' => 'task',
            'user_id' => null,
            'is_self_service' => 1,
            'self_service_groups' => ['groups' => [strval($group->id)], 'users' => []],
        ]);

        $regularTask = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
            'element_type' => 'task',
            'user_id' => $user->id,
            'is_self_service' => 0,
        ]);

        $statusFilter = json_encode([
            [
                'subject' => ['type' => 'Status'],
                'operator' => '=',
                'value' => 'Self Service',
            ],
        ]);

        $response = $this->actingAs($user, 'api')->get(route('api.tasks.index', [
            'pmql' => '(user_id = ' . $user->id . ') AND (status = "In Progress")',
            'advanced_filter' => $statusFilter,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $returnedIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($selfServiceTask->id, $returnedIds);
        $this->assertNotContains($regularTask->id, $returnedIds);
    }

    public function testGetScreenFields()
    {
        $this->be($this->user);

        $screen = Screen::factory()->create([
            'config' => json_decode(
                file_get_contents(
                    base_path('tests/Fixtures/rich_text_screen.json')
                )
            ),
        ]);

        $bpmn = file_get_contents(base_path('tests/Fixtures/single_task_with_screen.bpmn'));
        $bpmn = str_replace('pm:screenRef="1"', 'pm:screenRef="' . $screen->id . '"', $bpmn);
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
        ]);

        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('node_1');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);

        $task = $request->tokens->last();

        // Calls new API by name
        $route = route('api.getScreenFields.show', ['task' => $task->id]);
        $response = $this->apiCall('GET', $route);

        $response->assertStatus(200);

        // Check JSON response
        $jsonResponse = $response->json();

        $expectedFields = [
            'richtext1',
            'textarea1',
            'input1',
            'richtext2',
            'textarea2',
            'submit1',
        ];

        $this->assertEquals($expectedFields, $jsonResponse);
    }

    public function testGetAllTaskTypes()
    {
        $request = ProcessRequest::factory()->create();

        $taskAttributes = [
            'process_request_id' => $request->id,
            'process_id' => $request->process_id,
            'element_type' => 'task',
            'user_id' => $this->user->id,
        ];

        $userTask = ProcessRequestToken::factory()->create($taskAttributes);
        $scriptTask = ProcessRequestToken::factory()->create([...$taskAttributes, 'element_type' => 'scriptTask']);
        $gatewayTask = ProcessRequestToken::factory()->create([...$taskAttributes, 'element_type' => 'gateway']);

        $queryString = http_build_query([
            'pmql' => "process_request_id = {$request->id}",
        ]);
        $response = $this->apiCall('GET', '/tasks?' . $queryString);

        // Assert default behavior: only human tasks
        $response = $response->json();
        $this->assertCount(1, $response['data']);
        $this->assertEquals($userTask->id, $response['data'][0]['id']);

        $queryString = http_build_query([
            'pmql' => "process_request_id = {$request->id}",
            'all_tasks' => 1,
        ]);
        $response = $this->apiCall('GET', '/tasks?' . $queryString);

        // Assert all tasks returned
        $response = $response->json();
        $this->assertCount(3, $response['data']);
        $this->assertEquals($userTask->id, $response['data'][0]['id']);
        $this->assertEquals($scriptTask->id, $response['data'][1]['id']);
        $this->assertEquals($gatewayTask->id, $response['data'][2]['id']);
    }

    /**
     * Test that the free-text filter parameter works on the tasks endpoint.
     */
    public function testFilterByFreeTextSearch()
    {
        $request = ProcessRequest::factory()->create();

        $matchingTask = ProcessRequestToken::factory()->create([
            'element_type' => 'task',
            'element_name' => 'UniqueSearchableTaskName',
            'status' => 'ACTIVE',
            'process_request_id' => $request->id,
        ]);

        ProcessRequestToken::factory()->create([
            'element_type' => 'task',
            'element_name' => 'OtherTask',
            'status' => 'ACTIVE',
            'process_request_id' => $request->id,
        ]);

        $response = $this->apiCall('GET', route('api.' . $this->resource . '.index', [
            'filter' => 'UniqueSearchableTaskName',
            'per_page' => 50,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertTrue(
            collect($data)->contains('id', $matchingTask->id),
            'Expected matching task to appear in filtered results'
        );
        $this->assertFalse(
            collect($data)->contains('element_name', 'OtherTask'),
            'Expected non-matching task to be excluded from filtered results'
        );
    }

    /**
     * Build a query that joins process_request_tokens to itself via a
     * subquery aliased `cte`, matching the pattern used by the Saved Search
     * package. Any WHERE clauses added afterwards must use qualified column
     * names or they will hit an "ambiguous column" error.
     */
    private function buildJoinedSubquery()
    {
        $query = ProcessRequestToken::query()->where('element_type', 'task');

        $subQuery = clone $query;
        $subQuery->select('id', 'updated_at')->latest('updated_at')->take(25);

        $query->select('process_request_tokens.*')
            ->joinSub($subQuery, 'cte', function ($join) {
                $join->on('process_request_tokens.id', '=', 'cte.id');
            });

        return $query;
    }

    /**
     * Test that scopeFilter's non-indexed branch works when the query has
     * a join that introduces duplicate column names (Saved Search pattern).
     */
    public function testScopeFilterWorksWithJoinedSubquery()
    {
        $request = ProcessRequest::factory()->create();

        ProcessRequestToken::factory()->create([
            'element_type' => 'task',
            'element_name' => 'JoinTestTask',
            'status' => 'ACTIVE',
            'process_request_id' => $request->id,
        ]);

        $query = $this->buildJoinedSubquery();
        $query->filter('JoinTestTask');

        $results = $query->get();

        $this->assertNotEmpty($results);
        $this->assertTrue($results->contains('element_name', 'JoinTestTask'));
    }

    /**
     * Test that scopeFilter's indexed-search branch (numeric filter path)
     * uses a qualified `id` column so it works with the Saved Search join.
     */
    public function testScopeFilterIndexedSearchNumericWorksWithJoinedSubquery()
    {
        Setting::updateOrCreate(
            ['key' => 'indexed-search'],
            ['config' => ['enabled' => true]]
        );

        $request = ProcessRequest::factory()->create();

        $task = ProcessRequestToken::factory()->create([
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'process_request_id' => $request->id,
        ]);

        $query = $this->buildJoinedSubquery();
        $query->filter((string) $task->id);

        $results = $query->get();

        $this->assertTrue($results->contains('id', $task->id));
    }
}
