<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 */
class ProcessControllerTest extends TestCase
{

    use DatabaseTransactions;
    use WithFaker;
    use ResourceAssertionsTrait;

    protected $resource = 'processes';
    protected $structure = [
        'id',
        'type',
        'attributes' => [
            'uuid',
            'process_category_uuid',
            'user_uuid',
            'description',
            'name',
            'status',
            'created_at',
            'updated_at'
        ]
    ];

    /**
     * Test to verify our processes listing api endpoint works without any filters
     */
    public function testProcessesListing()
    {
        $user = $this->authenticateAsAdmin();
        $this->actingAs($user, 'api');
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
                'total_pages' => ceil(($initialCount + $countProcesses) / $perPage),
            ]
        );
    }

    /**
     * Test to verify our processes listing API endpoint works without any filters
     */
    public function testFiltering()
    {
        $user = $this->authenticateAsAdmin();
        $this->actingAs($user, 'api');
        $perPage = 10;
        $initialActiveCount = Process::where('status','ACTIVE')->count();
        $initialInactiveCount = Process::where('status','INACTIVE')->count();

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
            '?filter=ACTIVE&include=category&per_page=' . $perPage,
            [
                'total' => $initialActiveCount + $processActive['num'],
                'count' => $perPage,
                'per_page' => $perPage,
            ]
        );
        //verify include
        $response->assertJsonStructure(['*' => ['relationships' => ['category']]], $response->json('data'));

        //Get active processes
        $response = $this->assertCorrectModelListing(
            '?filter=INACTIVE&include=category&per_page=' . $perPage,
            [
                'total' => $initialInactiveCount + $processInactive['num'],
                'count' => $perPage,
                'per_page' => $perPage,
            ]
        );
        //verify include
        $response->assertJsonStructure(['*' => ['relationships' => ['category']]], $response->json('data'));
    }

    /**
     * Test to verify our processes listing api endpoint works with sorting
     */
    public function testSorting()
    {
        $user = $this->authenticateAsAdmin();
        // Create some processes
        factory(Process::class)->create([
            'name' => 'aaaaaa',
            'description' => 'bbbbbb'
        ]);
        factory(Process::class)->create([
            'name' => 'zzzzz',
            'description' => 'yyyyy'
        ]);
        $response = $this->actingAs($user, 'api')->json('GET', route('processes.index')
            . '?order_by=name&order_direction=asc');
        $response->assertStatus(200);
        $data = $response->json('data')[0]['attributes'];
        $this->assertEquals('aaaaaa', $data['name']);
        $this->assertEquals('bbbbbb', $data['description']);

        $response = $this->actingAs($user, 'api')->json('GET', route('processes.index')
            . '?order_by=name&order_direction=DESC');
        $response->assertStatus(200);
        $data = $response->json('data')[0]['attributes'];
        $this->assertEquals('zzzzz', $data['name']);
        $this->assertEquals('yyyyy', $data['description']);

        $response = $this->actingAs($user, 'api')->json('GET', route('processes.index')
            . '?order_by=description&order_direction=desc');
        $response->assertStatus(200);
        $data = $response->json('data')[0]['attributes'];
        $this->assertEquals('zzzzz', $data['name']);
        $this->assertEquals('yyyyy', $data['description']);
    }

    public function testPagination()
    {
        $user = $this->authenticateAsAdmin();

        // Number of processes in the tables at the moment of starting the test
        $initialRows = Process::all()->count();

        // Number of rows to be created for the test
        $rowsToAdd = 7;

        // Now we create the specified number of processes
        factory(Process::class, $rowsToAdd)->create();

        // The first page should have 5 items;
        $response = $this->actingAs($user, 'api')
                            ->json('GET', route('processes.index', ['per_page' => 5, 'page' => 1]));
        $response->assertJsonCount(5, 'data');

        // The second page should have the modulus of 2+$initialRows
        $response = $this->actingAs($user, 'api')
            ->json('GET', route('processes.index', ['per_page' => 5, 'page' => 2]));
        $response->assertJsonCount((2 + $initialRows) % 5, 'data');
    }

    /**
     * Create an login API as an administrator user.
     *
     */
    private function authenticateAsAdmin(): User
    {
        $admin = factory(User::class)->create([]);
        return $admin;
    }

    /**
     * Test the creation of processes
     */
    public function testProcessCreation()
    {
        //Login as an admin user
        $user = $this->authenticateAsAdmin();
        $this->actingAs($user, 'api');

        //Create a process without category
        $this->assertCorrectModelCreation(
            Process::class, [
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => null,
            ]
        );

        //Create a process without sending the category
        $this->assertCorrectModelCreation(
            Process::class, [
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => static::$DO_NOT_SEND,
            ]
        );

        //Create a process with a category
        $category = factory(ProcessCategory::class)->create();
        $this->assertCorrectModelCreation(
            Process::class, [
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => $category->uuid_text,
            ]
        );

    }

    /**
     * Test the required fields
     */
    public function testCreateProcessFieldsValidation()
    {
        $user = $this->authenticateAsAdmin();
        $this->actingAs($user, 'api');
        //Test to create a process with an empty name
        $this->assertModelCreationFails(
            Process::class,
            [
                'name' => null,
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => static::$DO_NOT_SEND
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
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => static::$DO_NOT_SEND
            ],
            //Fields that should fail
            [
                'name'
            ]
        );

        //Test to create a process with a process category uuid that does not exist
        $this->assertModelCreationFails(
            Process::class,
            [
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => 'uuid-not-exists'
            ],
            //Fields that should fail
            [
                'process_category_uuid'
            ]
        );
    }

    /**
     * Test show process
     *
     */
    public function testShowProcess()
    {
        $user = $this->authenticateAsAdmin();
        $this->actingAs($user, 'api');

        //Create a new process without category
        $process = factory(Process::class)->create([
            'process_category_uuid' => null
        ]);

        //Test that is correctly displayed
        $this->assertModelShow($process->uuid_text, []);

        //Test that is correctly displayed with null category
        $this->assertModelShow($process->uuid_text, ['category'])
            ->assertJsonFragment([['category' => ['data' => null]]]);

        //Create a new process with category
        $process = factory(Process::class)->create();

        //Test that is correctly displayed with category
        $this->assertModelShow($process->uuid_text, ['category']);
    }

    /**
     * Process deletion
     *
     * Test the process deletion
     */
    public function testsProcessDeletion()
    {
        $user = $this->authenticateAsAdmin();
        $this->actingAs($user, 'api');

        //Create a new process
        $process = factory(Process::class)->create();

        //Delete the process created
        $this->assertCorrectModelDeletion($process->uuid_text);

        //Create a request without collaboration
        $request = factory(ProcessRequest::class)->create([
            'process_collaboration_uuid' => null
        ]);
        $process = $request->process;

        //Delete the process created
        $this->assertModelDeletionFails($process->uuid_text, [
            'requests'
        ]);

        //Create a request with collaboration
        $process = factory(Process::class)->create([
            'process_category_uuid' => null
        ]);
        factory(ProcessCollaboration::class)->create([
            'process_uuid' => $process->uuid
        ]);

        //Delete the process created
        $this->assertModelDeletionFails($process->uuid_text, [
            'collaborations'
        ]);
    }

    /*
    + Test update process
     */
    public function testUpdateProcess()
    {
        $user = $this->authenticateAsAdmin();
        $this->actingAs($user, 'api');
        //Test to update name process
        $name = $this->faker->name;
        $this->assertModelUpdate(
            Process::class,
            [
                'name' => $name,
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => static::$DO_NOT_SEND,
            ]
        );

        //Test update process category of null
        $this->assertModelUpdate(
            Process::class,
            [
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => null
            ]
        );

        //Test update process category
        $this->assertModelUpdate(
            Process::class,
            [
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => factory(ProcessCategory::class)->create()->uuid_text
            ]
        );
    }


    /**
     * Test update process
     */
    public function testUpdateProcessFails()
    {
        $user = $this->authenticateAsAdmin();
        $this->actingAs($user, 'api');
        //Test to update name and description if required
        $this->assertModelUpdateFails(
            Process::class,
            [
                'name' => '',
                'description' => '',
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => static::$DO_NOT_SEND,
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
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => 'process_category_uuid_not_exists'
            ],
            [
                'process_category_uuid'
            ]
        );

        //Test validate name is unique
        $name = 'Some name';
        factory(Process::class)->create(['name' => $name]);
        $this->assertModelUpdateFails(
            Process::class,
            [
                'name' => $name,
                'user_uuid' => static::$DO_NOT_SEND,
                'process_category_uuid' => static::$DO_NOT_SEND,
            ],
            [
                'name'
            ]
        );
    }

}
