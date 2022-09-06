<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PermissionSeeder;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
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
        $request = factory(ProcessRequest::class)->create();
        // Create some tokens
        factory(ProcessRequestToken::class, 20)->create([
            'process_request_id' => $request->id,
        ]);
        //Get a page of tokens
        $route = route('api.' . $this->resource . '.index', ['per_page' => 10, 'page' => 2]);
        $response = $this->apiCall('GET', $route);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
    }

    /**
     * You only see tasks that belong to you if you are not admin
     */
    public function testGetListAssignedTasks()
    {
        $user_1 = factory(User::class)->create();
        $user_2 = factory(User::class)->create();
        $this->user = $user_1;

        $request = factory(ProcessRequest::class)->create();
        // Create some tokens
        factory(ProcessRequestToken::class, 2)->create([
            'process_request_id' => $request->id,
            'status' => 'ACTIVE',
            'user_id' => $user_1->id,
        ]);
        factory(ProcessRequestToken::class, 3)->create([
            'process_request_id' => $request->id,
            'status' => 'ACTIVE',
            'user_id' => $user_2->id,
        ]);
        //Get a page of tokens
        //Since PR #3470, user_id is required as parameter
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
        //Run the permission seeder
        (new PermissionSeeder)->run();

        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();

        $user_1 = factory(User::class)->create();
        $user_1->giveDirectPermission('view-all_requests');
        // $user_1->refresh();
        // $this->flushSession();

        $this->user = $user_1;

        $user_2 = factory(User::class)->create();

        $request = factory(ProcessRequest::class)->create();
        // Create some closed tasks
        factory(ProcessRequestToken::class, 3)->create([
            'process_request_id' => $request->id,
            'status' => 'CLOSED',
            'user_id' => $user_2->id,
        ]);
        factory(ProcessRequestToken::class, 1)->create([
            'process_request_id' => $request->id,
            'status' => 'ACTIVE',
            'user_id' => $user_2->id,
        ]);
        //Get a page of tokens
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
        $user_1 = factory(User::class)->create();
        $user_2 = factory(User::class)->create();

        $process = factory(Process::class)->create();
        $category = factory(ProcessCategory::class)->create(['status' => 'ACTIVE', 'is_system' => true]);
        $systemProcess = factory(Process::class)->create(['process_category_id' => $category->id]);
        // Create some tokens
        factory(ProcessRequestToken::class, 2)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'user_id' => $user_1->id,
        ]);
        factory(ProcessRequestToken::class, 3)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'user_id' => $user_2->id,
        ]);
        factory(ProcessRequestToken::class, 1)->create([
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
        $request = factory(ProcessRequest::class)->create(['name' => $name]);
        // Create some tokens
        $newEntity = factory(ProcessRequestToken::class)->create([
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
        $request = factory(ProcessRequest::class)->create();
        // Create some tokens
        factory(ProcessRequestToken::class, 10)->create([
            'status' => 'ACTIVE',
            'process_request_id' => $request->id,
        ]);
        factory(ProcessRequestToken::class, 10)->create([
            'status' => 'CLOSED',
            'process_request_id' => $request->id,
        ]);

        //Get active tokens
        $route = route('api.' . $this->resource . '.index', ['per_page' => 10, 'status' => 'ACTIVE']);
        $response = $this->apiCall('GET', $route);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
    }

    /**
     * Test that we filter only for human tasks
     */
    public function testFilteringGetOnlyHumanTasks()
    {
        $request = factory(ProcessRequest::class)->create();

        // Create some startEvent tokens
        factory(ProcessRequestToken::class, 5)->create([
            'element_type' => 'startEvent',
            'process_request_id' => $request->id,
        ]);

        // Create some scriptTask tokens
        factory(ProcessRequestToken::class, 5)->create([
            'element_type' => 'scriptTask',
            'process_request_id' => $request->id,
        ]);

        // Create some task tokens
        factory(ProcessRequestToken::class, 5)->create([
            'element_type' => 'task',
            'process_request_id' => $request->id,
        ]);

        //Get tasks
        $route = route('api.' . $this->resource . '.index', ['per_page' => 100]);
        $response = $this->apiCall('GET', $route);

        //Verify the status
        $response->assertStatus(200);

        //Verify the element types
        $types = collect($response->json()['data'])->pluck('element_type')->unique()->toArray();
        $this->assertEquals($types, ['task']);
    }

    /**
     * Test list of tokens sorting by completed_at
     */
    public function testSorting()
    {
        $request = factory(ProcessRequest::class)->create();
        // Create some tokens
        factory(ProcessRequestToken::class)->create([
            'user_id' => $this->user->id,
            'completed_at' => null,
            'process_request_id' => $request->id,
        ]);
        factory(ProcessRequestToken::class)->create([
            'user_id' => $this->user->id,
            'completed_at' => Carbon::now(),
            'process_request_id' => $request->id,
        ]);

        //List sorted by completed_at returns as first row {"completed_at": null}
        $route = route('api.' . $this->resource . '.index', ['order_by' => 'completed_at', 'order_direction' => 'asc']);
        $response = $this->apiCall('GET', $route);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        //Verify the first row
        $firstRow = $response->json('data')[0];
        $this->assertArraySubset(['completed_at' => null], $firstRow);
    }

    public function testSortByRequestName()
    {
        //$request = factory(ProcessRequest::class)->create();

        factory(ProcessRequestToken::class, 5)->create([
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
        $request = factory(ProcessRequest::class)->create();
        // Number of tokens in the tables at the moment of starting the test
        $initialRows = ProcessRequestToken::all()->count();

        // Number of rows to be created for the test
        $rowsToAdd = 10;

        // Now we create the specified number of tokens
        factory(ProcessRequestToken::class, $rowsToAdd)->create([
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
        $request = factory(ProcessRequest::class)->create();
        //Create a new process without category
        $token = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $request->id,
        ]);

        //Test that is correctly displayed
        $route = route('api.' . $this->resource . '.show', [$token->id]);
        $response = $this->apiCall('GET', $route);
        //Check the status
        $response->assertStatus(200);
        //Check the structure
        $response->assertJsonStructure($this->structure);
    }

    /**
     * Test get a token including user child.
     */
    public function testShowTaskWithUser()
    {
        $request = factory(ProcessRequest::class)->create();
        //Create a new process without category
        $token = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $request->id,
        ]);

        //Test that is correctly displayed
        $route = route('api.' . $this->resource . '.show', [$token->id, 'include' => 'user,definition']);
        $response = $this->apiCall('GET', $route);
        //Check the status
        $this->assertStatus(200, $response);
        //Check the structure
        $response->assertJsonStructure($this->structure);
        $response->assertJsonStructure(['user' => ['id', 'email'], 'definition' => []]);
    }

    public function testUpdateTask()
    {
        $this->user = factory(User::class)->create(); // normal user
        $request = factory(ProcessRequest::class)->create();
        $token = factory(ProcessRequestToken::class)->create([
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
        ]);
        $params = ['status' => 'COMPLETED', 'data' => ['foo' => '<p>bar</p>']];
        WorkflowManager::shouldReceive('completeTask')
            ->once()
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), ['foo' => 'bar']);
        $response = $this->apiCall('PUT', '/tasks/' . $token->id, $params);
        $this->assertStatus(200, $response);
    }

    public function testUpdateTaskRichText()
    {
        // $this->user = factory(User::class)->create(); // normal user
        $screen = factory(Screen::class)->create([
            'config' => json_decode(
                file_get_contents(
                    base_path('tests/Fixtures/rich_text_screen.json')
                )
            ),
        ]);

        $bpmn = file_get_contents(base_path('tests/Fixtures/single_task_with_screen.bpmn'));
        $bpmn = str_replace('pm:screenRef="1"', 'pm:screenRef="' . $screen->id . '"', $bpmn);
        $process = factory(Process::class)->create([
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
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), [
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
        $request = factory(ProcessRequest::class)->create();

        //Create a new process without category
        $token = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $request->id,
        ]);
        $url = route('api.' . $this->resource . '.show', [$token->id, 'include' => 'user,definition']);

        //The call is done without an authenticated user so it should return 401
        $response = $this->actingAs(factory(User::class)->create())
            ->json('GET', $url, []);
        $response->assertStatus(401);
    }

    public function testSelfServeTasks()
    {
        $this->user = $user = factory(User::class)->create(['status' => 'ACTIVE']);
        $otherUser = factory(User::class)->create(['status' => 'ACTIVE']);

        $group1 = factory(Group::class)->create();
        factory(GroupMember::class)->create([
            'member_id' => $user->id,
            'member_type' => User::class,
            'group_id' => $group1->id,
        ]);
        $group2 = factory(Group::class)->create();
        factory(GroupMember::class)->create([
            'member_id' => $user->id,
            'member_type' => User::class,
            'group_id' => $group2->id,
        ]);

        $params = [
            'status' => 'ACTIVE',
            'user_id' => null,
            'is_self_service' => true,
        ];

        $selfServiceTaskOriginal = factory(ProcessRequestToken::class)->create(
            array_merge($params, [
                'self_service_groups' => [(string) $group1->id],
            ])
        );
        $selfServiceTaskGroups = factory(ProcessRequestToken::class)->create(
            array_merge($params, [
                'self_service_groups' => [
                    'groups' => [(string) $group2->id],
                ],
            ])
        );
        $selfServiceTaskUsers = factory(ProcessRequestToken::class)->create(
            array_merge($params, [
                'self_service_groups' => [
                    'users' => [(string) $user->id, (string) $otherUser->id],
                ],
            ])
        );
        $selfServiceTaskOtherUser = factory(ProcessRequestToken::class)->create(
            array_merge($params, [
                'self_service_groups' => [
                    'users' => [(string) $otherUser->id],
                ],
            ])
        );
        $selfServiceTaskAssigned = factory(ProcessRequestToken::class)->create(
            array_merge($params, [
                'self_service_groups' => [
                    'users' => [(string) $group1->id],
                ],
                'user_id' => $user->id,
            ])
        );
        $regularTask = factory(ProcessRequestToken::class)->create([
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
        $process = factory(Process::class)->create([
            'bpmn' => $bpmn,
        ]);
        factory(ProcessNotificationSetting::class)->create([
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
}
