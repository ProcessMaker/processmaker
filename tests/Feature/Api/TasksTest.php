<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Facades\ProcessMaker\RollbackProcessRequest;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Mockery;
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
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ActivityActivatedNotification;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to tokens list and show
 * the creation, update and deletion are controller by the engine
 * and should not be changed by endpoints
 *
 * @group process_tests
 */
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

    /**
     * Return all tasks if we are only requesting closed tasks and if
     * the user can view the request.
     */
    public function testGetListClosedTasks()
    {
        // Run the permission seeder
        (new PermissionSeeder)->run();

        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();

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
            "richtext1",
            "textarea1",
            "input1",
            "richtext2",
            "textarea2",
            "submit1",
        ];

        $this->assertEquals($expectedFields, $jsonResponse);
    }
}
