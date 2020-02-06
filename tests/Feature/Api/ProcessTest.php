<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
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
        'updated_at'
    ];

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testProcessesListing()
    {
        $initialCount = Process::count();
        // Create some processes
        $countProcesses = 20;
        factory(Process::class, $countProcesses)->create();
        //Get a page of processes
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
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        // Add process permission to user
        $this->user->permissions()->attach(Permission::byName('view-processes'));

        // Get the initial count
        $initialCount = Process::count();

        // Create some processes
        $bpmn = trim(Process::getProcessTemplate('SingleTask.bpmn'));
        $process = factory(Process::class)->create(['bpmn' => $bpmn]);
        $process->usersCanStart('StartEventUID')->attach($this->user->id);

        //Get a page of processes
        $page = 1;
        $perPage = 10;
        $this->assertCorrectModelListing(
            '?page=' . $page . '&per_page=' . $perPage,
            [
                'total' => 1,
                'count' => 1,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int)ceil(($initialCount + 1) / $perPage),
            ]
        );
    }

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testProcessesListingWithNoAdminGroup()
    {
        // We create a user that isn't administrator
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        //Create default All Users group
        $group = factory(Group::class)->create([
            'name' => 'Test Group',
            'status' => 'ACTIVE'
        ]);
        $group->save();
        $group->refresh();

        //Add user to group
        factory(GroupMember::class)->create([
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
        $process = factory(Process::class)->create(['bpmn' => $bpmn]);
        $process->groupsCanStart('StartEventUID')->attach($group->id);

        //Get a page of processes
        $page = 1;
        $perPage = 10;
        $this->assertCorrectModelListing(
            '?page=' . $page . '&per_page=' . $perPage,
            [
                'total' => 1,
                'count' => 1,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int)ceil(($initialCount + 1) / $perPage),
            ]
        );
    }

    /**
     * Verifies if the list of processes that can be started is correct
     */
    public function testStartRequestList()
    {
        ProcessRequest::query()->delete();

        //get process with start event
        $file = Process::getProcessTemplatesPath() . '/SingleTask.bpmn';
        $bpmn = file_get_contents($file);

        // Create 3 categories
        $cat1 = factory(ProcessCategory::class)->create(['name' => 'Z cat', 'status' => 'ACTIVE']);
        $cat2 = factory(ProcessCategory::class)->create(['name' => 'A cat', 'status' => 'ACTIVE']);
        $cat3 = factory(ProcessCategory::class)->create(['name' => 'M cat', 'status' => 'ACTIVE']);

        // Create processes for every category
        factory(Process::class, 2)->create(['process_category_id' => $cat1->id]);
        factory(Process::class)->create(['process_category_id' => $cat2->id, 'name' => 'ZProcess', 'status' => 'ACTIVE', 'bpmn' => $bpmn]);
        factory(Process::class)->create(['process_category_id' => $cat2->id, 'name' => 'BProcess', 'status' => 'ACTIVE', 'bpmn' => $bpmn]);
        factory(Process::class, 2)->create(['process_category_id' => $cat3->id]);

        $response = $this->apiCall('GET', route('api.processes.start', ['order_by' => 'category.name,name']));
        $this->assertStatus(200, $response);

        // The returned list should be ordered category and then by process name, alphabetically
        $this->assertEquals($cat2->id, $response->getData()->data[0]->process_category_id);
        $this->assertEquals('BProcess', $response->getData()->data[0]->name);
    }


    public function testProcessEventsTrigger()
    {
        $process = factory(Process::class)->create([
            'bpmn' => Process::getProcessTemplate('SingleTask.bpmn')
        ]);

        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        $route = route('api.process_events.trigger', $process);

        $response = $this->apiCall('POST', $route . '?event=StartEventUID');
        $this->assertStatus(403, $response);

        $process->usersCanStart('StartEventUID')->attach([
            $this->user->id => ['method' => 'START', 'node' => 'StartEventUID']
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
        $process = factory(Process::class)->create([
            'bpmn' => Process::getProcessTemplate('SingleTask.bpmn')
        ]);

        $route = route('api.process_events.trigger', $process);

        $initialData = [
            'Field1' => 'Value of Field 1',
            'Field2' => 'htt://www.files.com'
        ];

        $response = $this->apiCall('POST', $route . '?event=StartEventUID', $initialData);
        $this->assertStatus(201, $response);

        // Verify that the initial data was stored
        $requestRoute =route('api.requests.show', ['request'=>$response->getData()->id]) . '?include=data';
        $requestResponse = $this->apiCall('GET',$requestRoute );

        // Assert structure
        $requestResponse->assertJsonStructure([
            'data' => ['Field1', 'Field2']
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
        $newEntity = factory(Process::class)->create(['name' => $processName]);
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
        $initialActiveCount = Process::active()->count();
        $initialInactiveCount = Process::inactive()->count();

        // Create some processes
        $processActive = [
            'num' => 10,
            'status' => 'ACTIVE'
        ];
        $processInactive = [
            'num' => 15,
            'status' => 'INACTIVE'
        ];
        factory(Process::class, $processActive['num'])->create(['status' => $processActive['status']]);
        factory(Process::class, $processInactive['num'])->create(['status' => $processInactive['status']]);

        //Get active processes
        $response = $this->assertCorrectModelListing(
            '?status=active&include=category&per_page=' . $perPage,
            [
                'total' => $initialActiveCount + $processActive['num'],
                'count' => $perPage,
                'per_page' => $perPage,
            ]
        );
        //verify include
        $response->assertJsonStructure(['*' => ['category']], $response->json('data'));

        //Get active processes
        $response = $this->assertCorrectModelListing(
            '?status=inactive&include=category,user&per_page=' . $perPage,
            [
                'total' => $initialInactiveCount + $processInactive['num'],
                'count' => $perPage,
                'per_page' => $perPage,
            ]
        );
        //verify include
        $response->assertJsonStructure(['*' => ['category', 'user']], $response->json('data'));
    }

    /**
     * Test to verify our processes listing api endpoint works with sorting
     */
    public function testSorting()
    {
        // Create some processes
        factory(Process::class)->create([
            'name' => 'aaaaaa',
            'description' => 'bbbbbb'
        ]);
        factory(Process::class)->create([
            'name' => 'zzzzz',
            'description' => 'yyyyy'
        ]);

        //Test the list sorted by name returns as first row {"name": "aaaaaa"}
        $this->assertModelSorting('?order_by=name&order_direction=asc', [
            'name' => 'aaaaaa'
        ]);

        //Test the list sorted desc returns as first row {"name": "zzzzz"}
        $this->assertModelSorting('?order_by=name&order_direction=DESC', [
            'name' => 'zzzzz'
        ]);

        //Test the list sorted by description in desc returns as first row {"description": "yyyyy"}
        $this->assertModelSorting('?order_by=description&order_direction=desc', [
            'description' => 'yyyyy'
        ]);
    }

    /**
     * Test pagination of process list
     *
     */
    public function testPagination()
    {
        // Number of processes in the tables at the moment of starting the test
        $initialRows = Process::all()->count();

        // Number of rows to be created for the test
        $rowsToAdd = 7;

        // Now we create the specified number of processes
        factory(Process::class, $rowsToAdd)->create();

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
        //Create a process without category
        $this->assertModelCreationFails(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => null,
            ]
        );

        //Create a process without sending the category
        $this->assertCorrectModelCreation(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND,
                'has_timer_start_events' => static::$DO_NOT_SEND,
            ]
        );

        //Create a process with a category
        $category = factory(ProcessCategory::class)->create();
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
        $base = factory(Process::class)->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        //Add a bpmn content
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
        //Test to create a process with an empty name
        $this->assertModelCreationFails(
            Process::class,
            [
                'name' => null,
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND
            ],
            //Fields that should fail
            [
                'name'
            ]
        );

        //Test to create a process with duplicate name
        $name = 'Some name';
        factory(Process::class)->create(['name' => $name]);
        $this->assertModelCreationFails(
            Process::class,
            [
                'name' => $name,
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND
            ],
            //Fields that should fail
            [
                'name'
            ]
        );

        //Test to create a process with a process category id that does not exist
        $this->assertModelCreationFails(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => 'id-not-exists'
            ],
            //Fields that should fail
            [
                'process_category_id'
            ]
        );
    }

    /**
     * Test the creation of processes with BPMN definition
     */
    public function testValidateBpmnWhenCreatingAProcess()
    {
        $route = route('api.' . $this->resource . '.store');
        $base = factory(Process::class)->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        //Add a bpmn content
        $array['bpmn'] = trim(Process::getProcessTemplate('ProcessWithErrors.bpmn'));
        $response = $this->apiCall('POST', $route, $array);
        //A validation error should be displayed
        $response->assertStatus(422);
    }
    
    /**
     * Test the creation of processes with invalid XML posted
     */
    public function testValidateInvalidXmlWhenCreatingAProcess()
    {
        $route = route('api.' . $this->resource . '.store');
        $base = factory(Process::class)->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        //Add a bpmn content
        $array['bpmn'] = 'foo';
        $response = $this->apiCall('POST', $route, $array);
        //A validation error should be displayed
        $response->assertStatus(422);
    }

    /**
     * Test show process
     *
     */
    public function testShowProcess()
    {
        //Create a new process without category
        $process = factory(Process::class)->create([
            'process_category_id' => null
        ]);

        //Test that is correctly displayed
        $this->assertModelShow($process->id, []);

        //Test that is correctly displayed with null category
        $this->assertModelShow($process->id, ['category'])
            ->assertJsonFragment(['category' => null]);

        //Create a new process with category
        $process = factory(Process::class)->create();

        //Test that is correctly displayed including category and user
        $this->assertModelShow($process->id, ['category', 'user']);
    }

    /**
     * Test update process
     */
    public function testUpdateProcess()
    {
        //Seeder Permissions
        (new \PermissionSeeder())->run($this->user);

        //Test to update name process
        $name = $this->faker->sentence(3);
        $this->assertModelUpdate(
            Process::class,
            [
                'name' => $name,
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND,
                'description' => 'test'
            ]
        );
    }

    /**
     * Test update process
     */
    public function testUpdateProcessWithCategoryNull()
    {
        //Seeder Permissions
        (new \PermissionSeeder())->run($this->user);

        //Test update process category to null
        $this->assertModelUpdateFails(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'name' => 'A new name',
                'process_category_id' => null,
                'description' => 'test'
            ]
        );
    }

    /**
     * Test update process
     */
    public function testUpdateProcessWithCategory()
    {
        //Seeder Permissions
        (new \PermissionSeeder())->run($this->user);

        //Test update process category
        $this->assertModelUpdate(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'name' => 'Another name',
                'process_category_id' => factory(ProcessCategory::class)->create()->id,
                'description' => 'test'
            ]
        );
    }

    /**
     * Test update process with invalid parameters
     */
    public function testUpdateProcessFails()
    {
        //Test to update name and description if required
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
                'description'
            ]
        );

        //Test update process category of null
        $this->assertModelUpdateFails(
            Process::class,
            [
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => 'process_category_id_not_exists'
            ],
            [
                'process_category_id'
            ]
        );

        //Test validate name is unique
        $name = 'Some name';
        factory(Process::class)->create(['name' => $name]);
        $this->assertModelUpdateFails(
            Process::class,
            [
                'name' => $name,
                'user_id' => static::$DO_NOT_SEND,
                'process_category_id' => static::$DO_NOT_SEND,
            ],
            [
                'name'
            ]
        );
    }

    /**
     * Test Update BPMN endpoint.
     */
    public function testUpdateBPMN()
    {
        //Seeder Permissions
        (new \PermissionSeeder())->run($this->user);

        $process = factory(Process::class)->create([
            'bpmn' => Process::getProcessTemplate('OnlyStartElement.bpmn')
        ]);
        $id = $process->id;
        $newBpmn = trim(Process::getProcessTemplate('SingleTask.bpmn'));
        $route = route('api.' . $this->resource . '.update', [$id]);
        $response = $this->apiCall('PUT', $route, [
            'name' => 'test name',
            'description' => 'test description',
            'bpmn' => $newBpmn
        ]);
        //validate status
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
        $process = factory(Process::class)->create();
        $id = $process->id;
        $newBpmn = 'Invalid BPMN content';
        $route = route('api.' . $this->resource . '.update', [$id]);
        $response = $this->apiCall('PUT', $route, [
            'bpmn' => $newBpmn
        ]);
        //validate status
        $this->assertStatus(422, $response);
        $response->assertJsonStructure($this->errorStructure);
    }

    /**
     * Tests the archiving and restoration of a process
     */
    public function testArchiveRestore()
    {
        // Generate an active process and get its ID
        $process = factory(Process::class)->create([
            'status' => 'ACTIVE'
        ]);
        $id = $process->id;

        // Assert that the process is listed
        $response = $this->apiCall('GET', '/processes');
        $response->assertJsonFragment(['id' => $id]);

        // Assert that the process is not listed in the archive
        $response = $this->apiCall('GET', '/processes?status=inactive');
        $response->assertJsonMissing(['id' => $id]);

        // Archive the process
        $response = $this->apiCall('DELETE', "/processes/{$id}");
        $response->assertStatus(204);

        // Assert that the process is listed in the archive
        $response = $this->apiCall('GET', '/processes?status=inactive');
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
        $response = $this->apiCall('GET', '/processes?status=inactive');
        $response->assertJsonMissing(['id' => $id]);
    }

    /**
     * Tests updating a start permission for a node
     */
    public function testStartPermissionForNode()
    {
        $user = factory(User::class)->create();
        $bpmn = trim(Process::getProcessTemplate('SingleTask.bpmn'));
        $node = 'StartEventUID';
        $process = factory(Process::class)->create([
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
        $this->user = factory(User::class)->create();

        // Add process permission to user
        $this->user->permissions()->attach(Permission::byName('view-processes'));

        // Prepare a process
        $bpmn = trim(Process::getProcessTemplate('SingleTask.bpmn'));
        $node = 'StartEventUID';

        $process = factory(Process::class)->create([
            'status' => 'ACTIVE',
            'bpmn' => $bpmn,
        ]);
        // Need to check that sync works with param.....
        $process->usersCanStart($node)->sync([$this->user->id => ['method' => 'START', 'node' => $node]]);

        $other_process = factory(Process::class)->create([
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
        $process = factory(Process::class)->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/ProcessStartTimerEvent.bpmn'),
        ]);
        // Assertion: Process::has_timer_start_events should return true
        $this->assertTrue($process->has_timer_start_events);

        // Loads a process without an start timer event
        $process = factory(Process::class)->create([
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
        $base = factory(Process::class)->make([
            'user_id' => static::$DO_NOT_SEND,
            'process_category_id' => static::$DO_NOT_SEND,
        ]);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        //Add a bpmn content
        $array['bpmn'] = file_get_contents(__DIR__.'/processes/C.4.0-export.bpmn');
        $response = $this->apiCall('POST', $route, $array);
        $response->assertStatus(422);
        $error = $response->json();
        $this->assertArrayHasKey('errors', $error);
        $this->assertTrue(in_array('Multiple diagrams are not supported', $error['errors']['bpmn']));
    }

    public function testUpdateScriptCategories()
    {
        $screen = factory(Process::class)->create();
        $url = route('api.processes.update', $screen);
        $params = [
            'name' => 'name process',
            'description' => 'Description.',
            'process_category_id' => factory(ProcessCategory::class)->create()->getKey() . ',' . factory(ProcessCategory::class)->create()->getKey()
        ];
        $response = $this->apiCall('PUT', $url, $params);
        $response->assertStatus(200);
    }
}
