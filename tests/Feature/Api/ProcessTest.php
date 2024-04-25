<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use Faker\Generator as Faker;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Bookmark;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\WorkflowServiceProvider as PM;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 * @group process_tests
 */
class ProcessTest extends TestCase
{
    use WithFaker;
    use RequestHelper;
    use ResourceAssertionsTrait;

    public $withPermissions = true;

    protected $resource = 'processes';

    protected $structure = [
        'id',
        'process_category_id',
        'user_id',
        'description',
        'name',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testProcessesListing()
    {
        $initialCount = Process::count();
        // Create some processes
        $countProcesses = 20;
        Process::factory()->count($countProcesses)->create();
        // Get a page of processes
        $page = 2;
        $perPage = 10;
        $this->assertCorrectModelListing(
            '?page=' . $page . '&per_page=' . $perPage,
            [
                'total' => $initialCount + $countProcesses,
                'count' => $perPage,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int) ceil(($initialCount + $countProcesses) / $perPage),
            ]
        );
    }

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testProcessesListingWithNoAdminUser()
    {
        // We create a user that isn't administrator
        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        // Add process permission to user
        $this->user->permissions()->attach(Permission::byName('view-processes'));

        // Get the initial count
        $initialCount = Process::count();

        // Create some processes
        $bpmn = trim(Process::getProcessTemplate('SingleTask.bpmn'));
        $process = Process::factory()->create(['bpmn' => $bpmn]);
        $process->usersCanStart('StartEventUID')->attach($this->user->id);

        // Get a page of processes
        $page = 1;
        $perPage = 10;
        $this->assertCorrectModelListing(
            '?page=' . $page . '&per_page=' . $perPage,
            [
                'total' => 1,
                'count' => 1,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int) ceil(($initialCount + 1) / $perPage),
            ]
        );
    }

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testProcessesListingWithNoAdminGroup()
    {
        // We create a user that isn't administrator
        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        // Create default All Users group
        $group = Group::factory()->create([
            'name' => 'Test Group',
            'status' => 'ACTIVE',
        ]);
        $group->save();
        $group->refresh();

        // Add user to group
        GroupMember::factory()->create([
            'member_id' => $this->user->id,
            'member_type' => User::class,
            'group_id' => $group->id,
        ]);
        $this->user->save();
        $this->user->refresh();

        // Create process permissions for the group
        $group->permissions()->attach(Permission::byName('view-processes'));

        // Get the initial count
        $initialCount = Process::count();

        // Create a process
        $bpmn = trim(Process::getProcessTemplate('SingleTask.bpmn'));
        $process = Process::factory()->create(['bpmn' => $bpmn]);
        $process->groupsCanStart('StartEventUID')->attach($group->id);

        // Get a page of processes
        $page = 1;
        $perPage = 10;
        $this->assertCorrectModelListing(
            '?page=' . $page . '&per_page=' . $perPage,
            [
                'total' => 1,
                'count' => 1,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int) ceil(($initialCount + 1) / $perPage),
            ]
        );
    }

    /**
     * Verifies if the list of processes that can be started is correct
     */
    public function testStartRequestList()
    {
        ProcessRequest::query()->delete();

        // get process with start event
        $file = Process::getProcessTemplatesPath() . '/SingleTask.bpmn';
        $bpmn = file_get_contents($file);

        // Create 3 categories
        $cat1 = ProcessCategory::factory()->create(['name' => 'Z cat', 'status' => 'ACTIVE']);
        $cat2 = ProcessCategory::factory()->create(['name' => 'A cat', 'status' => 'ACTIVE']);
        $cat3 = ProcessCategory::factory()->create(['name' => 'M cat', 'status' => 'ACTIVE']);

        // Create processes for every category
        Process::factory()->count(2)->create(['process_category_id' => $cat1->id]);
        Process::factory()->create(['process_category_id' => $cat2->id, 'name' => 'ZProcess', 'status' => 'ACTIVE', 'bpmn' => $bpmn]);
        Process::factory()->create(['process_category_id' => $cat2->id, 'name' => 'BProcess', 'status' => 'ACTIVE', 'bpmn' => $bpmn]);
        Process::factory()->count(2)->create(['process_category_id' => $cat3->id]);

        $response = $this->apiCall('GET', route('api.processes.start', ['order_by' => 'category.name,name']));
        $this->assertStatus(200, $response);

        $responseData = $response->getData()->data;
        if (is_array($responseData)) {
            $responseItem = $responseData[0];
        } elseif (is_object($responseData)) {
            $responseItem = $responseData->{'0'};
        }

        // The returned list should be ordered category and then by process name, alphabetically
        $this->assertEquals($cat2->id, $responseItem->process_category_id);
        $this->assertEquals('BProcess', $responseItem->name);
    }

    /**
     * Verifies if is returning a list that not contains processes with start events like timers, conditionals, signals and messages ..
     */
    public function testStartRequestListProcessesWithoutEventDefinitions()
    {
        ProcessRequest::query()->delete();

        // get process with start event
        $startSingleEventFile = Process::getProcessTemplatesPath() . '/StartSingleEvent.bpmn';
        $startTimerEventFile = Process::getProcessTemplatesPath() . '/StartTimerEvent.bpmn';
        $startConditionalEventFile = Process::getProcessTemplatesPath() . '/StartConditionalEvent.bpmn';
        $startSignalEventFile = Process::getProcessTemplatesPath() . '/StartSignalEvent.bpmn';
        $startMessageEventFile = Process::getProcessTemplatesPath() . '/StartMessageEvent.bpmn';
        $startSingleEventBpmn = file_get_contents($startSingleEventFile);
        $startTimerEventBpmn = file_get_contents($startTimerEventFile);
        $startConditionalEventBpmn = file_get_contents($startConditionalEventFile);
        $startSignalEventBpmn = file_get_contents($startSignalEventFile);
        $startMessageEventBpmn = file_get_contents($startMessageEventFile);

        // Create category
        $category = ProcessCategory::factory()->create(['name' => 'A cat', 'status' => 'ACTIVE']);

        // Create processes for every category
        Process::factory()->create(['process_category_id' => $category->id, 'name' => 'AProcess', 'status' => 'ACTIVE', 'bpmn' => $startSingleEventBpmn]);
        Process::factory()->create(['process_category_id' => $category->id, 'name' => 'BProcess', 'status' => 'ACTIVE', 'bpmn' => $startTimerEventBpmn]);
        Process::factory()->create(['process_category_id' => $category->id, 'name' => 'CProcess', 'status' => 'ACTIVE', 'bpmn' => $startConditionalEventBpmn]);
        Process::factory()->create(['process_category_id' => $category->id, 'name' => 'DProcess', 'status' => 'ACTIVE', 'bpmn' => $startSignalEventBpmn]);
        Process::factory()->create(['process_category_id' => $category->id, 'name' => 'EProcess', 'status' => 'ACTIVE', 'bpmn' => $startMessageEventBpmn]);

        $response = $this->apiCall('GET', route('api.processes.start', ['order_by' => 'category.name,name', 'without_event_definitions' => 'true']));
        $this->assertStatus(200, $response);

        $responseData = $response->getData()->data;
        $responseMeta = $response->getData()->meta;

        // The process list length should be only 1 because there is only one process that is single start event ..
        $this->assertEquals(1, count($responseData));
        // The total processes count in pagination should be only 1 because there is only one process that is single start event ..
        $this->assertEquals(1, $responseMeta->total);

        if (is_array($responseData)) {
            $responseItem = $responseData[0];
        } elseif (is_object($responseData)) {
            $responseItem = $responseData->{'0'};
        }

        // The returned list should be ordered category and then by process name, alphabetically
        $this->assertEquals($category->id, $responseItem->process_category_id);
        // Should return only AProcess because is the only process with single start event..
        $this->assertEquals('AProcess', $responseItem->name);
    }

    /**
     * Verifies if is returning a list that contains processes with start events like conditionals, signals and messages ..
     */
    public function testStartRequestListProcessesWithEventDefinitions()
    {
        ProcessRequest::query()->delete();

        // get process with start event
        $startSingleEventFile = Process::getProcessTemplatesPath() . '/StartSingleEvent.bpmn';
        $startTimerEventFile = Process::getProcessTemplatesPath() . '/StartTimerEvent.bpmn';
        $startConditionalEventFile = Process::getProcessTemplatesPath() . '/StartConditionalEvent.bpmn';
        $startSignalEventFile = Process::getProcessTemplatesPath() . '/StartSignalEvent.bpmn';
        $startMessageEventFile = Process::getProcessTemplatesPath() . '/StartMessageEvent.bpmn';
        $startSingleEventBpmn = file_get_contents($startSingleEventFile);
        $startTimerEventBpmn = file_get_contents($startTimerEventFile);
        $startConditionalEventBpmn = file_get_contents($startConditionalEventFile);
        $startSignalEventBpmn = file_get_contents($startSignalEventFile);
        $startMessageEventBpmn = file_get_contents($startMessageEventFile);

        // Create category
        $category = ProcessCategory::factory()->create(['name' => 'A cat', 'status' => 'ACTIVE']);

        // Create processes for every category
        Process::factory()->create(['process_category_id' => $category->id, 'name' => 'AProcess', 'status' => 'ACTIVE', 'bpmn' => $startSingleEventBpmn]);
        Process::factory()->create(['process_category_id' => $category->id, 'name' => 'BProcess', 'status' => 'ACTIVE', 'bpmn' => $startTimerEventBpmn]);
        Process::factory()->create(['process_category_id' => $category->id, 'name' => 'CProcess', 'status' => 'ACTIVE', 'bpmn' => $startConditionalEventBpmn]);
        Process::factory()->create(['process_category_id' => $category->id, 'name' => 'DProcess', 'status' => 'ACTIVE', 'bpmn' => $startSignalEventBpmn]);
        Process::factory()->create(['process_category_id' => $category->id, 'name' => 'EProcess', 'status' => 'ACTIVE', 'bpmn' => $startMessageEventBpmn]);

        $response = $this->apiCall('GET', route('api.processes.start', ['order_by' => 'category.name,name', 'without_event_definitions' => 'false']));
        $this->assertStatus(200, $response);

        $responseData = $response->getData()->data;
        $responseMeta = $response->getData()->meta;

        // The process list length should be 4 because api should return the processes start single event, start conditional, start message, start signal..
        $this->assertEquals(4, count($responseData));
        // The total processes count in pagination should be 4 because api should return the processes start single event, start conditional, start message, start signal..
        $this->assertEquals(4, $responseMeta->total);
    }

    /**
     * Verifies if a process manager can start a request
     */
    public function testProcessManagerCanStartARequest()
    {
        // Create a non admin user:
        $processManagerUser = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);
        $otherUser = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);
        $this->user = $processManagerUser;

        $noAssignedBpmn = Process::getProcessTemplate('SingleTask.bpmn');
        $processManagerBpmn = str_replace('id="StartEventUID"', 'id="StartEventUID" pm:assignment="process_manager"', $noAssignedBpmn);
        $assignedBpmn = str_replace('id="StartEventUID"', 'id="StartEventUID" pm:assignment="user" pm:assignedUsers="' . $this->user->id . '"', $noAssignedBpmn);

        $processWithManager = Process::factory()->create([
            'bpmn' => $processManagerBpmn,
            'properties' => ['manager_id' => $processManagerUser->id],
        ]);

        $processAssigned = Process::factory()->create([
            'bpmn' => $assignedBpmn,
            'properties' => ['manager_id' => $otherUser->id],
        ]);

        $processForOtherUser = Process::factory()->create([
            'bpmn' => $noAssignedBpmn,
            'properties' => ['manager_id' => $otherUser->id],
        ]);

        // Call endpoint that lists the processes that the user can start
        $response = $this->apiCall('GET', route('api.processes.start', ['order_by' => 'category.name,name']));
        $this->assertStatus(200, $response);

        $responseData = $response->getData()->data;

        // just the process with assignment as process manager and the process assigned to the user should be returned
        $this->assertEquals(count($responseData), 2);
        $this->assertTrue(in_array($responseData[0]->id, [$processWithManager->id, $processAssigned->id]));
        $this->assertTrue(in_array($responseData[1]->id, [$processWithManager->id, $processAssigned->id]));
    }

    public function testProcessManagerCanStartProcessWithTwoStartEvents()
    {
        // Create a non admin user:
        $processManagerUser = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);
        $otherUser = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        $processBpmn = \file_get_contents(__DIR__ . '/processes/SingleTaskProcessManager.bpmn');
        $processBpmn = str_replace('{$otherUser_id}', $otherUser->id, $processBpmn);

        $process = Process::factory()->create([
            'bpmn' => $processBpmn,
            'properties' => ['manager_id' => $processManagerUser->id],
        ]);

        // Call endpoint that lists the processes that the user can start
        $this->user = $otherUser;
        $response = $this->apiCall('POST', route('api.process_events.trigger', [$process->id, 'event' => 'node_3']));
        $this->assertStatus(201, $response);

        $this->user = $processManagerUser;
        $response = $this->apiCall('POST', route('api.process_events.trigger', [$process->id, 'event' => 'node_1']));
        $this->assertStatus(201, $response);
    }

    /**
     * Verify the new request start events do not include web entry start events
     */
    public function testWebEntryFilteredFromStartEvents()
    {
        $file = __DIR__ . '/processes/SingleTask.bpmn';
        $regularBpmn = file_get_contents($file);

        $file = __DIR__ . '/processes/RegularStartAndWebEntry.bpmn';
        $webEntryBpmn = file_get_contents($file);

        Process::factory()->create(['status' => 'ACTIVE', 'bpmn' => $regularBpmn]);
        Process::factory()->create(['status' => 'ACTIVE', 'bpmn' => $webEntryBpmn]);

        $response = $this->apiCall('GET', route('api.processes.start'));
        $startEvents = collect($response->json()['data'])->flatMap(function ($process) {
            return collect($process['startEvents'])->map(function ($startEvent) {
                return $startEvent['name'];
            });
        });

        $startEvents = $startEvents->toArray();
        sort($startEvents);

        $this->assertEquals(['Start Event', 'regular'], $startEvents);
    }

    public function testProcessEventsTrigger()
    {
        $process = Process::factory()->create([
            'bpmn' => Process::getProcessTemplate('SingleTask.bpmn'),
        ]);

        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        $route = route('api.process_events.trigger', $process);

        $response = $this->apiCall('POST', $route . '?event=StartEventUID');
        $this->assertStatus(403, $response);

        $process->usersCanStart('StartEventUID')->attach([
            $this->user->id => ['method' => 'START', 'node' => 'StartEventUID'],
        ]);

        $response = $this->apiCall('POST', $route . '?event=StartEventUID');
        $this->assertStatus(201, $response);
    }

    /**
     * Verifies that a new request can be created
     */
    public function testCreateRequest()
    {
        $this->withoutExceptionHandling();
        // Load the process to be used in the test
        $process = Process::factory()->create([
            'bpmn' => Process::getProcessTemplate('SingleTask.bpmn'),
        ]);

        $route = route('api.process_events.trigger', $process);

        $initialData = [
            'Field1' => 'Value of Field 1',
            'Field2' => 'htt://www.files.com',
        ];

        $response = $this->apiCall('POST', $route . '?event=StartEventUID', $initialData);
        $this->assertStatus(201, $response);

        // Verify that the initial data was stored
        $requestRoute = route('api.requests.show', ['request' => $response->getData()->id]) . '?include=data';
        $requestResponse = $this->apiCall('GET', $requestRoute);

        // Assert structure
        $requestResponse->assertJsonStructure([
            'data' => ['Field1', 'Field2'],
        ]);

        // Assert that stored values are correct
        $this->assertEquals($initialData['Field1'], $requestResponse->getData()->data->Field1);
        $this->assertEquals($initialData['Field2'], $requestResponse->getData()->data->Field2);
    }

    /**
     * Test to verify that the list dates are in the correct format (yyyy-mm-dd H:i+GMT)
     */
    public function testProcessListDates()
    {
        $processName = 'processTestTimezone';
        $newEntity = Process::factory()->create(['name' => $processName]);
        $route = route('api.' . $this->resource . '.index', ['filter' => $processName]);
        $response = $this->apiCall('GET', $route);

        $this->assertEquals(
            $newEntity->updated_at->format('c'),
            $response->getData()->data[0]->updated_at
        );

        $this->assertEquals(
            $newEntity->created_at->format('c'),
            $response->getData()->data[0]->created_at
        );
    }

    /**
     * Test to verify our processes listing API endpoint works without any filters
     */
    public function testFiltering()
    {
        $perPage = 10;

        $initialNotArchivedCount = Process::notArchived()->count();
        $initialArchivedCount = Process::archived()->count();

        // Create some processes
        $processActive = [
            'num' => 10,
            'status' => 'ACTIVE',
        ];
        $processInactive = [
            'num' => 15,
            'status' => 'INACTIVE',
        ];
        $processArchived = [
            'num' => 20,
            'status' => 'ARCHIVED',
        ];
        Process::factory()->count($processActive['num'])->create(['status' => $processActive['status']]);
        Process::factory()->count($processInactive['num'])->create(['status' => $processInactive['status']]);
        Process::factory()->count($processArchived['num'])->create(['status' => $processArchived['status']]);

        // Get active processes
        $response = $this->assertCorrectModelListing(
            '?status=active&include=category&per_page=' . $perPage,
            [
                'total' => $initialNotArchivedCount + $processActive['num'] + $processInactive['num'],
                'count' => $perPage,
                'per_page' => $perPage,
            ]
        );
        // verify include
        $response->assertJsonStructure(['*' => ['category']], $response->json('data'));

        // Get active processes
        $response = $this->assertCorrectModelListing(
            '?status=archived&include=category,user&per_page=' . $perPage,
            [
                'total' => $initialArchivedCount + $processArchived['num'],
                'count' => $perPage,
                'per_page' => $perPage,
            ]
        );
        // verify include
        $response->assertJsonStructure(['*' => ['category', 'user']], $response->json('data'));
    }

    /**
     * Test to verify our processes listing api endpoint works with sorting
     */
    public function testSorting()
    {
        // Create some processes
        Process::factory()->create([
            'name' => 'aaaaaa',
            'description' => 'bbbbbb',
        ]);
        Process::factory()->create([
            'name' => 'zzzzz',
            'description' => 'yyyyy',
        ]);

        // Test the list sorted by name returns as first row {"name": "aaaaaa"}
        $this->assertModelSorting('?order_by=name&order_direction=asc', [
            'name' => 'aaaaaa',
        ]);

        // Test the list sorted desc returns as first row {"name": "zzzzz"}
        $this->assertModelSorting('?order_by=name&order_direction=DESC', [
            'name' => 'zzzzz',
        ]);

        // Test the list sorted by description in desc returns as first row {"description": "yyyyy"}
        $this->assertModelSorting('?order_by=description&order_direction=desc', [
            'description' => 'yyyyy',
        ]);
    }

    /**
     * Test filter by bookmark
     */
    public function testFilterBookmarked()
    {
        // This not will return the bookmark
        Process::factory()->count(5)->create();
        $response = $this->apiCall('GET', route('api.processes.index', ['per_page' => 5, 'page' => 1]));
        $response->assertJsonCount(5, 'data');
        $this->assertEquals(0, $response->json()['data'][0]['bookmark_id']);

        // This will return with bookmark
        $user = Auth::user();
        $process = Process::factory()->create();
        Bookmark::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
        ]);
        $response = $this->apiCall('GET',
            route('api.processes.index', ['per_page' => 5, 'page' => 1, 'bookmark' => true])
        );
        $response->assertJsonCount(5, 'data');
    }

    /**
     * Test filter by Category
     */
    public function testFilterCategory()
    {
        // Create Category
        $categoryA = ProcessCategory::factory()->create();
        $categoryB = ProcessCategory::factory()->create();
        // Now we create process related to this
        Process::factory()->count(5)->create([
            'process_category_id' => $categoryB->id,
        ]);
        // Get process without category
        $response = $this->apiCall('GET', route('api.processes.index', ['per_page' => 5, 'page' => 1]));
        $response->assertJsonCount(5, 'data');
        // Get process related categoryA
        $response = $this->apiCall('GET', route('api.processes.index', ['category' => $categoryA->id]));
        $response->assertJsonCount(0, 'data');
        // Get process related categoryB
        $response = $this->apiCall('GET', route('api.processes.index', ['category' => $categoryB->id]));
        $response->assertJsonCount(5, 'data');
    }

    /**
     * Test pagination of process list
     */
    public function testPagination()
    {
        // Number of processes in the tables at the moment of starting the test
        $initialRows = Process::all()->count();

        // Number of rows to be created for the test
        $rowsToAdd = 7;

        // Now we create the specified number of processes
        Process::factory()->count($rowsToAdd)->create();

        // The first page should have 5 items;
        $response = $this->apiCall('GET', route('api.processes.index', ['per_page' => 5, 'page' => 1]));
        $response->assertJsonCount(5, 'data');

        // The second page should have the modulus of 2+$initialRows
        $response = $this->apiCall('GET', route('api.processes.index', ['per_page' => 5, 'page' => 2]));
        $response->assertJsonCount((2 + $initialRows) % 5, 'data');
    }

    /**
     * Test the creation of processes
     */
    public function testProcessCreation()
    {
        // Create a process without category
        $this->assertModelCreationFails(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => null,
            ]
        );

        // Create a process without sending the category
        $this->assertCorrectModelCreation(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND,
                'has_timer_start_events' => static::$DO_NOT_SEND,
            ]
        );

        // Create a process with a category
        $category = ProcessCategory::factory()->create();
        $this->assertCorrectModelCreation(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => $category->id,
                'has_timer_start_events' => static::$DO_NOT_SEND,
            ]
        );
    }

    /**
     * Test the creation of processes with BPMN definition
     */
    public function testCreateProcessWithBPMN()
    {
        $route = route('api.' . $this->resource . '.store');
        $base = Process::factory()->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        // Add a bpmn content
        $array['bpmn'] = trim(Process::getProcessTemplate('OnlyStartElement.bpmn'));
        $response = $this->apiCall('POST', $route, $array);
        $response->assertStatus(201);
        $response->assertJsonStructure($this->structure);
        $data = $response->json();
        $process = Process::where('id', $data['id'])->first();
        $this->assertEquals($array['bpmn'], trim($process->bpmn));
    }

    /**
     * Test the required fields
     */
    public function testCreateProcessFieldsValidation()
    {
        // Test to create a process with an empty name
        $this->assertModelCreationFails(
            Process::class,
            [
                'name' => null,
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND,
            ],
            // Fields that should fail
            [
                'name',
            ]
        );

        // Test to create a process with duplicate name
        $name = 'Some name';
        Process::factory()->create(['name' => $name]);
        $this->assertModelCreationFails(
            Process::class,
            [
                'name' => $name,
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND,
            ],
            // Fields that should fail
            [
                'name',
            ]
        );

        // Test to create a process with a process category id that does not exist
        $this->assertModelCreationFails(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => 'id-not-exists',
            ],
            // Fields that should fail
            [
                'process_category_id',
            ]
        );
    }

    /**
     * Test the creation of processes with BPMN definition
     */
    public function testValidateBpmnWhenCreatingAProcess()
    {
        $route = route('api.' . $this->resource . '.store');
        $base = Process::factory()->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        // Add a bpmn content
        $array['bpmn'] = trim(Process::getProcessTemplate('ProcessWithErrors.bpmn'));
        $response = $this->apiCall('POST', $route, $array);
        // A validation error should be displayed
        $response->assertStatus(422);
    }

    /**
     * Test the creation of processes with invalid XML posted
     */
    public function testValidateInvalidXmlWhenCreatingAProcess()
    {
        $route = route('api.' . $this->resource . '.store');
        $base = Process::factory()->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        // Add a bpmn content
        $array['bpmn'] = 'foo';
        $response = $this->apiCall('POST', $route, $array);
        // A validation error should be displayed
        $response->assertStatus(422);
    }

    /**
     * Test show process
     */
    public function testShowProcess()
    {
        $this->markTestSkipped('FOUR-6653');

        // Create a new process without category
        $process = Process::factory()->create([
            'process_category_id' => null,
        ]);

        // Test that is correctly displayed
        $this->assertModelShow($process->id, []);

        // Test that is correctly displayed with null category
        $this->assertModelShow($process->id, ['category'])
            ->assertJsonFragment(['category' => []]);

        // Create a new process with category
        $process = Process::factory()->create();

        // Test that is correctly displayed including category and user
        $this->assertModelShow($process->id, ['category', 'user']);
    }

    /**
     * Test update process
     */
    public function testUpdateProcess()
    {
        // Seeder Permissions
        (new PermissionSeeder())->run($this->user);

        // Test to update name process
        $name = $this->faker->sentence(3);
        $this->assertModelUpdate(
            Process::class,
            [
                'name' => $name,
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND,
                'description' => 'test',
            ]
        );
    }

    /**
     * Test update process
     */
    public function testUpdateProcessWithCategoryNull()
    {
        // Seeder Permissions
        (new PermissionSeeder())->run($this->user);

        // Test update process category to null
        $this->assertModelUpdateFails(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'name' => 'A new name',
                'process_category_id' => null,
                'description' => 'test',
            ]
        );
    }

    /**
     * Test update process
     */
    public function testUpdateProcessWithCategory()
    {
        // Seeder Permissions
        (new PermissionSeeder())->run($this->user);

        // Test update process category
        $this->assertModelUpdate(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'name' => 'Another name',
                'process_category_id' => ProcessCategory::factory()->create()->id,
                'description' => 'test',
            ]
        );
    }

    /**
     * Test update process with invalid parameters
     */
    public function testUpdateProcessFails()
    {
        // Test to update name and description if required
        $this->assertModelUpdateFails(
            Process::class,
            [
                'name' => '',
                'description' => '',
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND,
            ],
            [
                'name',
                'description',
            ]
        );

        // Test update process category of null
        $this->assertModelUpdateFails(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => 'process_category_id_not_exists',
            ],
            [
                'process_category_id',
            ]
        );

        // Test validate name is unique
        $name = 'Some name';
        Process::factory()->create(['name' => $name]);
        $this->assertModelUpdateFails(
            Process::class,
            [
                'name' => $name,
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND,
            ],
            [
                'name',
            ]
        );
    }

    /**
     * Test Update BPMN endpoint.
     */
    public function testUpdateBPMN()
    {
        // Seeder Permissions
        (new PermissionSeeder())->run($this->user);

        $process = Process::factory()->create([
            'bpmn' => Process::getProcessTemplate('OnlyStartElement.bpmn'),
        ]);
        $id = $process->id;
        $newBpmn = trim(Process::getProcessTemplate('SingleTask.bpmn'));
        $route = route('api.' . $this->resource . '.update', [$id]);
        $response = $this->apiCall('PUT', $route, [
            'name' => 'test name',
            'description' => 'test description',
            'bpmn' => $newBpmn,
        ]);
        // validate status
        $this->assertStatus(200, $response);
        $response->assertJsonStructure($this->structure);
        $updatedProcess = Process::where('id', $id)->first();
        $this->assertEquals($newBpmn, trim($updatedProcess->bpmn));
    }

    /**
     * Test Update BPMN endpoint with and invalid BPMN content.
     */
    public function testUpdateInvalidBPMN()
    {
        $process = Process::factory()->create();
        $id = $process->id;
        $newBpmn = 'Invalid BPMN content';
        $route = route('api.' . $this->resource . '.update', [$id]);
        $response = $this->apiCall('PUT', $route, [
            'bpmn' => $newBpmn,
        ]);
        // validate status
        $this->assertStatus(422, $response);
        $response->assertJsonStructure($this->errorStructure);
    }

    /**
     * Tests the archiving and restoration of a process
     */
    public function testArchiveRestore()
    {
        // Generate an active process and get its ID
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
        ]);
        $id = $process->id;

        // Assert that the process is listed
        $response = $this->apiCall('GET', '/processes');
        $response->assertJsonFragment(['id' => $id]);

        // Assert that the process is not listed in the archive
        $response = $this->apiCall('GET', '/processes?status=archived');
        $response->assertJsonMissing(['id' => $id]);

        // Archive the process
        $response = $this->apiCall('DELETE', "/processes/{$id}");
        $response->assertStatus(204);

        // Assert that the process is listed in the archive
        $response = $this->apiCall('GET', '/processes?status=archived');
        $response->assertJsonFragment(['id' => $id]);

        // Assert that the process is not listed on the main index
        $response = $this->apiCall('GET', '/processes');
        $response->assertJsonMissing(['id' => $id]);

        // Restore the process
        $response = $this->apiCall('PUT', "/processes/{$id}/restore");
        $response->assertStatus(200);

        // Assert that the process is listed
        $response = $this->apiCall('GET', '/processes');
        $response->assertJsonFragment(['id' => $id]);

        // Assert that the process is not listed in the archive
        $response = $this->apiCall('GET', '/processes?status=archived');
        $response->assertJsonMissing(['id' => $id]);
    }

    /**
     * Tests updating a start permission for a node
     */
    public function testStartPermissionForNode()
    {
        $user = User::factory()->create();
        $bpmn = trim(Process::getProcessTemplate('SingleTask.bpmn'));
        $node = 'StartEventUID';
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'bpmn' => $bpmn,
        ]);

        $definitions = $process->getDefinitions();
        $element = $definitions->findElementById($node);
        $element->setAttributeNS(PM::PROCESS_MAKER_NS, 'assignment', 'user');
        $element->setAttributeNS(PM::PROCESS_MAKER_NS, 'assignedUsers', $user->id);
        $process->bpmn = $definitions->saveXML();
        $process->save();

        $this->assertEquals(1, $process->usersCanStart($node)->count());
        $this->assertEquals(
            $user->id,
            $process->usersCanStart($node)->first()->id
        );

        // test that they are removed
        $definitions = $process->getDefinitions();
        $element = $definitions->findElementById($node);
        $element->removeAttributeNS(PM::PROCESS_MAKER_NS, 'assignment');
        $element->removeAttributeNS(PM::PROCESS_MAKER_NS, 'assignedUsers');
        $process->bpmn = $definitions->saveXML();
        $process->save();

        $this->assertEquals(0, $process->usersCanStart($node)->count());
    }

    /**
     * Tests we only return processes that have nodes that the user can start
     */
    public function testStartProcessesWithPermission()
    {
        $this->user = User::factory()->create();

        // Add process permission to user
        $this->user->permissions()->attach(Permission::byName('view-processes'));

        // Prepare a process
        $bpmn = trim(Process::getProcessTemplate('SingleTask.bpmn'));

        // Assign start event to $user
        $bpmn = \str_replace(
            '<startEvent id="StartEventUID"',
            '<startEvent id="StartEventUID" pm:assignment="user" pm:assignedUsers="' . $this->user->id . '"',
            $bpmn
        );

        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'bpmn' => $bpmn,
        ]);

        $other_process = Process::factory()->create([
            'status' => 'ACTIVE',
        ]);

        $route = route('api.' . $this->resource . '.index', ['include' => 'events']);
        $response = $this->actingAs($this->user)->apiCall('GET', $route);
        $response->assertStatus(200);

        $json = $response->json();
        $events = [];
        foreach ($json['data'] as $process) {
            $events = array_merge($events, $process['events']);
        }

        $this->assertEquals(1, count($events));
    }

    /**
     * Tests Process::hasTimerStartEvents()
     *
     * @return void
     */
    public function testHasPauseTimerStartEvents()
    {
        // Loads a process with an start timer event
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/ProcessStartTimerEvent.bpmn'),
        ]);
        // Assertion: Process::has_timer_start_events should return true
        $this->assertTrue($process->has_timer_start_events);

        // Loads a process without an start timer event
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/SingleTask.bpmn'),
        ]);
        // Assertion: Process::has_timer_start_events should return false
        $this->assertFalse($process->has_timer_start_events);
    }

    /**
     * Test the creation of processes with BPMN definition
     */
    public function testCreateProcessWithMultipleBPMNDiagrams()
    {
        $route = route('api.' . $this->resource . '.store');
        $base = Process::factory()->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        // Add a bpmn content
        $array['bpmn'] = file_get_contents(__DIR__ . '/processes/C.4.0-export.bpmn');
        $response = $this->apiCall('POST', $route, $array);
        $response->assertStatus(422);
        $error = $response->json();
        $this->assertArrayHasKey('errors', $error);
        $this->assertTrue(in_array('Multiple diagrams are not supported', $error['errors']['bpmn']));
    }

    public function testUpdateScriptCategories()
    {
        $screen = Process::factory()->create();
        $url = route('api.processes.update', $screen);
        $params = [
            'name' => 'name process',
            'description' => 'Description.',
            'process_category_id' => ProcessCategory::factory()->create()->getKey() . ',' . ProcessCategory::factory()->create()->getKey(),
        ];
        $response = $this->apiCall('PUT', $url, $params);
        $response->assertStatus(200);
    }

    public function testProcessManager()
    {
        $process = Process::factory()->create();
        $manager = User::factory()->create();

        $url = route('api.processes.update', $process);
        $response = $this->apiCall('PUT', $url, [
            'name' => 'Process with manager',
            'description' => 'Description.',
            'manager_id' => $manager->id,
        ]);
        $response->assertStatus(200);
        $process->refresh();

        $url = route('api.processes.show', $process);
        $response = $this->apiCall('GET', $url);
        $response->assertStatus(200);
        $this->assertEquals($manager->id, $process->manager->id);

        $url = route('api.processes.index', $process);
        $response = $this->apiCall('GET', $url . '?filter=Process+with+manager');
        $processJson = $response->json()['data'][0];
        $this->assertEquals($processJson['manager_id'], $process->manager->id);
    }

    public function testUpdateCancelRequest()
    {
        $process = Process::factory()->create();
        $url = route('api.processes.update', $process);

        $payload = [
            'name' => 'Process',
            'description' => 'Description.',
            'cancel_request' => [
                'users' => [],
                'groups' => [],
            ],
        ];
        $response = $this->apiCall('PUT', $url, $payload);
        $process->refresh();
        $this->assertNull($process->properties);

        $payload['cancel_request']['pseudousers'] = ['manager'];
        $response = $this->apiCall('PUT', $url, $payload);
        $process->refresh();
        $this->assertTrue($process->properties['manager_can_cancel_request']);

        $payload['cancel_request']['pseudousers'] = [];
        $response = $this->apiCall('PUT', $url, $payload);
        $process->refresh();
        $this->assertFalse($process->properties['manager_can_cancel_request']);
    }

    public function testUpdateProcessVersions()
    {
        $process = Process::factory()->create();
        $url = route('api.processes.update', ['process' => $process]);
        $params = [
            'name' => 'Process',
            'description' => 'Description',
        ];

        $response = $this->apiCall('PUT', $url, $params);
        $response->assertStatus(200);

        // Assert another published version is created.
        $this->assertEquals(2, $process->versions()->published()->count());
        $this->assertEquals(0, $process->versions()->draft()->count());
    }

    public function testUpdateDraftProcess()
    {
        $process = Process::factory()->create();
        $url = route('api.processes.update_draft', ['process' => $process]);
        $bpmn = '<?xml version="1.0" encoding="UTF-8"?><bpmn:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></bpmn:definitions>';
        $params = [
            'name' => 'Process',
            'description' => 'Description',
            'bpmn' => $bpmn,
        ];

        $response = $this->apiCall('PUT', $url, $params);
        $response->assertStatus(200);

        $draft = $process->versions()->draft()->first();
        $this->assertNotNull($draft);
        $this->assertEquals($bpmn, $draft->bpmn);
    }

    public function testDiscardDraft()
    {
        // Create draft.
        $process = Process::factory()->create();
        $url = route('api.processes.update_draft', ['process' => $process]);
        $params = [
            'name' => 'Process',
            'description' => 'Description',
        ];

        $response = $this->apiCall('PUT', $url, $params);
        $response->assertStatus(200);

        // Assert draft version is created.
        $this->assertEquals(1, $process->versions()->draft()->count());

        // Discard the draft.
        $url = route('api.processes.close', ['process' => $process]);
        $response = $this->apiCall('POST', $url, $params);

        // Assert draft version is deleted.
        $this->assertEquals(0, $process->versions()->draft()->count());
    }

    public function testTriggerStartEventWeb()
    {
        $this->withoutExceptionHandling();
        $process = Process::factory()->create([
            'bpmn' => Process::getProcessTemplate('SingleTask.bpmn'),
        ]);

        $route = route('process_events.trigger', [
            'process' => $process->id,
            'event' => 'StartEventUID',
        ]);
        $response = $this->webCall('GET', $route);

        // Assert redirect status.
        $response->assertStatus(302);
    }

    public function testGetMediaImagesRoute()
    {
        $process = Process::factory()->create();
        $url = route('api.processes.media', $process);
        $params = [
            'id' => $process->id,
        ];
        $response = $this->apiCall('GET', $url, $params);
        $response->assertStatus(200);
    }

    public function testDeleteMediaImages()
    {
        $process = Process::factory()->create();
        $imageContent = file_get_contents(__DIR__ . '/images/640x480.png');
        $imagePath = storage_path('app/test-image.jpg');

        file_put_contents($imagePath, $imageContent);

        $uploadedFile = new UploadedFile($imagePath, 'test-image.jpg', 'image/jpeg', null, true);
        $process->addMedia($uploadedFile)->toMediaCollection('images_carousel');

        $mediaImagen = $process->getFirstMedia('images_carousel');
        $uuid = $mediaImagen->uuid;
        $url = route('api.processes.delete-media', $process);
        $params = [
            'id' => $process->id,
            'uuid' => $uuid,
        ];
        $response = $this->apiCall('DELETE', $url, $params);
        $response->assertStatus(204);

        $this->assertDatabaseMissing('media', ['id' => $mediaImagen->id]);
    }
}
