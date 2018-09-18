<?php
namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Http\Controllers\Api\ResourceRequestsTrait;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 */
class RequestsTest extends TestCase
{

    use DatabaseTransactions;
    use WithFaker;
    use ResourceAssertionsTrait;
    use ResourceRequestsTrait;

    const API_TEST_URL = '/api/1.0/requests';
    const DEFAULT_PASS = 'password';

    const STRUCTURE = [
        'uuid',
        'process_uuid',
        'process_collaboration_uuid',
        'user_uuid',
        'participant_uuid',
        'status',
        'name',
        'completed_at',
        'initiated_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Initialize the controller tests
     *
     */
    protected function setUp()
    {
//        parent::setUp();
//        //Login as an valid user
//        $this->user = factory(User::class)->create();
//        $this->actingAs($this->user, 'api');

        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
        ]);
    }

    /**
     * Test verify the parameter required for create form
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $response = $this->actingAs($this->user, 'api')->json('POST', self::API_TEST_URL, []);

        //Validate the header status code
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Create new request successfully
     */
    public function testCreateRequest()
    {
        $process = factory(Process::class)->create();

        $response = $this->actingAs($this->user, 'api')->json('POST', self::API_TEST_URL, [
            'process_uuid' => $process->uuid_text,
            'process_collaboration_uuid' => null,
            'status' => 'ACTIVE',
            'name' => 'RequestName',
            'data' => '{}'
        ]);

        //Validate the header status code
        $response->assertStatus(201);
    }

    /**
     * Can not create a request with an existing requestname
     */
    public function testNotCreateRequestWithRequestNameExists()
    {
        factory(ProcessRequest::class)->create([
            'name' => 'duplicated name',
        ]);

        $process = factory(Process::class)->create();

        //Post requestname duplicated
        $response = $this->actingAs($this->user, 'api')->json('POST', self::API_TEST_URL, [
            'process_uuid' => $process->uuid_text,
            'process_collaboration_uuid' => null,
            'status' => 'ACTIVE',
            'name' => 'duplicated name',
            'data' => '{}'
        ]);

        //Validate the header status code
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Get a list of Requests without query parameters.
     */
    public function testListRequest()
    {
        ProcessRequest::query()->delete();

        factory(ProcessRequest::class, 10)->create();

        $response = $this->actingAs($this->user, 'api')->json('GET', self::API_TEST_URL);

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
     * Get a list of Request with parameters
     */
    public function testListRequestWithQueryParameter()
    {
        $requestname = 'mytestrequestname';

        factory(ProcessRequest::class)->create([
            'name' => $requestname,
        ]);

        //List Request with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=name&order_direction=DESC&filter=' . $requestname;
        $response = $this->actingAs($this->user, 'api')->json('GET', self::API_TEST_URL . $query);

        //Validate the header status code
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        // Verify return data
        $this->assertEquals(1, $response->json()['meta']['total']);
        $this->assertEquals('firstname', $response->json()['meta']['sort_by']);
        $this->assertEquals('DESC', $response->json()['meta']['sort_order']);
    }

//    /**
//     * Test to verify our processes listing api endpoint works without any filters
//     */
//    public function testProcessRequestsListing()
//    {
//        // Create some processes
//        $countProcessRequests = 20;
//        factory(ProcessRequest::class, $countProcessRequests)->create();
//
//        $response = $this->json('GET', route($this->resource . '.index'));
//        $response->assertStatus(200);
//        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
//    }
//
//    /**
//     * Test to verify our processes listing API endpoint works without any filters
//     */
//    public function testFiltering()
//    {
//        $perPage = 10;
//        $initialActiveCount = ProcessRequest::where('status','ACTIVE')->count();
//        $initialInactiveCount = ProcessRequest::where('status','INACTIVE')->count();
//
//        // Create some processes
//        $processActive = [
//            'num' => 10,
//            'status' => 'ACTIVE'
//        ];
//        $processInactive = [
//            'num' => 15,
//            'status' => 'INACTIVE'
//        ];
//        factory(ProcessRequest::class, $processActive['num'])->create(['status' => $processActive['status']]);
//        factory(ProcessRequest::class, $processInactive['num'])->create(['status' => $processInactive['status']]);
//
//        //Get active processes
//        $response = $this->assertCorrectModelListing(
//            '?filter=ACTIVE&include=category&per_page=' . $perPage,
//            [
//                'total' => $initialActiveCount + $processActive['num'],
//                'count' => $perPage,
//                'per_page' => $perPage,
//            ]
//        );
//        //verify include
//        $response->assertJsonStructure(['*' => ['category']], $response->json('data'));
//
//        //Get active processes
//        $response = $this->assertCorrectModelListing(
//            '?filter=INACTIVE&include=category&per_page=' . $perPage,
//            [
//                'total' => $initialInactiveCount + $processInactive['num'],
//                'count' => $perPage,
//                'per_page' => $perPage,
//            ]
//        );
//        //verify include
//        $response->assertJsonStructure(['*' => ['category']], $response->json('data'));
//    }
//
//    /**
//     * Test to verify our processes listing api endpoint works with sorting
//     */
//    public function testSorting()
//    {
//        // Create some processes
//        factory(ProcessRequest::class)->create([
//            'name' => 'aaaaaa',
//            'description' => 'bbbbbb'
//        ]);
//        factory(ProcessRequest::class)->create([
//            'name' => 'zzzzz',
//            'description' => 'yyyyy'
//        ]);
//
//        //Test the list sorted by name returns as first row {"name": "aaaaaa"}
//        $this->assertModelSorting('?order_by=name&order_direction=asc', [
//            'name' => 'aaaaaa'
//        ]);
//
//        //Test the list sorted desc returns as first row {"name": "zzzzz"}
//        $this->assertModelSorting('?order_by=name&order_direction=DESC', [
//            'name' => 'zzzzz'
//        ]);
//
//        //Test the list sorted by description in desc returns as first row {"description": "yyyyy"}
//        $this->assertModelSorting('?order_by=description&order_direction=desc', [
//            'description' => 'yyyyy'
//        ]);
//    }
//
//    /**
//     * Test pagination of process list
//     *
//     */
//    public function testPagination()
//    {
//        // Number of processes in the tables at the moment of starting the test
//        $initialRows = ProcessRequest::all()->count();
//
//        // Number of rows to be created for the test
//        $rowsToAdd = 7;
//
//        // Now we create the specified number of processes
//        factory(ProcessRequest::class, $rowsToAdd)->create();
//
//        // The first page should have 5 items;
//        $response = $this->json('GET', route('processes.index', ['per_page' => 5, 'page' => 1]));
//        $response->assertJsonCount(5, 'data');
//
//        // The second page should have the modulus of 2+$initialRows
//        $response = $this->json('GET', route('processes.index', ['per_page' => 5, 'page' => 2]));
//        $response->assertJsonCount((2 + $initialRows) % 5, 'data');
//    }
//
//    /**
//     * Test the creation of processes
//     */
//    public function testProcessRequestCreation()
//    {
//        $model = factory(ProcessRequest::class)->make();
//        $inputData = $model->toArray();
//        unset($inputData['user_uuid']);
//        $inputData['process_collaboration_uuid'] = $model->process_collaboration_uuid_text;
//        $inputData['process_uuid'] = $model->process_uuid_text;
//        $inputData['data'] = '{}';
//
//        $response = $this->json('POST',
//                                    route($this->resource . '.store'),
//                                    $inputData);
//        $response->assertStatus(201);
//        $response->assertJsonStructure($this->structure);
//
//        // Create a request without a collaboration
//        $model = factory(ProcessRequest::class)->make();
//        $inputData = $model->toArray();
//        unset($inputData['user_uuid']);
//        unset($inputData['process_collaboration_uuid']);
//        $inputData['process_uuid'] = $model->process_uuid_text;
//        $inputData['data'] = '{}';
//
//        $response = $this->json('POST',
//            route($this->resource . '.store'),
//            $inputData);
//        $response->assertStatus(201);
//        $response->assertJsonStructure($this->structure);
//    }
//
//    /**
//     * Test the required fields
//     */
//    public function testCreateProcessRequestFieldsValidation()
//    {
//        $errorData = ['name' => null, 'process_uuid' => 'wrong-id'];
//        $model = factory(ProcessRequest::class)->make($errorData);
//        $response = $this->json('POST',
//            route($this->resource . '.store'),
//            $model->toArray());
//
//        $response->assertStatus(422);
//        $response->assertJsonStructure(['errors' => array_keys($errorData)]);
//    }
//
//    /**
//     * Test show process
//     *
//     */
//    public function testShowProcessRequest()
//    {
//        $processRequest = factory(ProcessRequest::class)->create();
//
//        $response = $this->json('GET', route($this->resource . '.show', [$processRequest->uuid_text]));
//        $response->assertStatus(200);
//        $response->assertJsonStructure($this->structure);
//
//        $this->assertModelShow($processRequest->uuid_text, ['category'])
//            ->assertJsonFragment(['category' => null]);
//
//        //Create a new processRequest with category
//        $processRequest = factory(ProcessRequest::class)->create();
//
//        //Test that is correctly displayed with category
//        $this->assertModelShow($processRequest->uuid_text, ['category']);
//    }
//
//    /**
//     * ProcessRequest deletion
//     *
//     * Test the process deletion
//     */
//    public function testsProcessRequestDeletion()
//    {
//        //Create a new process
//        $process = factory(ProcessRequest::class)->create();
//
//        //Delete the process created
//        $this->assertCorrectModelDeletion($process->uuid_text);
//
//        //Create a request without collaboration
//        $request = factory(ProcessRequestRequest::class)->create([
//            'process_collaboration_uuid' => null
//        ]);
//        $process = $request->process;
//
//        //Delete the process created
//        $this->assertModelDeletionFails($process->uuid_text, [
//            'requests'
//        ]);
//
//        //Create a request with collaboration
//        $process = factory(ProcessRequest::class)->create([
//            'process_category_uuid' => null
//        ]);
//        factory(ProcessRequestCollaboration::class)->create([
//            'process_uuid' => $process->uuid
//        ]);
//
//        //Delete the process created
//        $this->assertModelDeletionFails($process->uuid_text, [
//            'collaborations'
//        ]);
//    }
//
//    /*
//    + Test update process
//     */
//    public function testUpdateProcessRequest()
//    {
//        //Test to update name process
//        $name = $this->faker->name;
//        $this->assertModelUpdate(
//            ProcessRequest::class,
//            [
//                'name' => $name,
//                'user_uuid' => static::$DO_NOT_SEND,
//                'process_category_uuid' => static::$DO_NOT_SEND,
//            ]
//        );
//
//        //Test update process category to null
//        $this->assertModelUpdate(
//            ProcessRequest::class,
//            [
//                'user_uuid' => static::$DO_NOT_SEND,
//                'process_category_uuid' => null
//            ]
//        );
//
//        //Test update process category
//        $this->assertModelUpdate(
//            ProcessRequest::class,
//            [
//                'user_uuid' => static::$DO_NOT_SEND,
//                'process_category_uuid' => factory(ProcessRequestCategory::class)->create()->uuid_text
//            ]
//        );
//    }
//
//
//    /**
//     * Test update process
//     */
//    public function testUpdateProcessRequestFails()
//    {
//        //Test to update name and description if required
//        $this->assertModelUpdateFails(
//            ProcessRequest::class,
//            [
//                'name' => '',
//                'description' => '',
//                'user_uuid' => static::$DO_NOT_SEND,
//                'process_category_uuid' => static::$DO_NOT_SEND,
//            ],
//            [
//                'name',
//                'description'
//            ]
//        );
//
//        //Test update process category of null
//        $this->assertModelUpdateFails(
//            ProcessRequest::class,
//            [
//                'user_uuid' => static::$DO_NOT_SEND,
//                'process_category_uuid' => 'process_category_uuid_not_exists'
//            ],
//            [
//                'process_category_uuid'
//            ]
//        );
//
//        //Test validate name is unique
//        $name = 'Some name';
//        factory(ProcessRequest::class)->create(['name' => $name]);
//        $this->assertModelUpdateFails(
//            ProcessRequest::class,
//            [
//                'name' => $name,
//                'user_uuid' => static::$DO_NOT_SEND,
//                'process_category_uuid' => static::$DO_NOT_SEND,
//            ],
//            [
//                'name'
//            ]
//        );
//    }

}
