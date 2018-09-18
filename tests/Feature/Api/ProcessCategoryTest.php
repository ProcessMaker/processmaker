<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 * @group process_tests
 */
class ProcessCategoryTest extends TestCase
{

    use DatabaseTransactions;
    use WithFaker;
    use ResourceAssertionsTrait;

    protected $user;
    protected $resource = 'process_categories';
    protected $structure = [
        'uuid',
        'name',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * Initialize the controller tests
     *
     */
    protected function setUp()
    {
        parent::setUp();
        //Login as an valid user
        $this->user = factory(User::class)->create();
        $this->actingAs($this->user, 'api');
    }

    /**
     * Test the creation of processes
     */
    public function testCreateProcessCategory()
    {
        //Create a process category
        $route = route($this->resource . '.store');
        $base = factory(ProcessCategory::class)->make();
        $response = $this->json('POST', $route, $base->toArray());
        //validate status create
        $response->assertStatus(201);
        //validate structure
        $response->assertJsonStructure($this->structure);
        //vValidate the correct information of the sent data
        $this->assertArraySubset($base->toArray(), $response->json());
    }

    /**
     * Test the required fields
     */
    public function testCreateNameRequired()
    {
        $route = route($this->resource . '.store');
        $base = factory(ProcessCategory::class)->make(['name' => null]);
        $response = $this->json('POST', $route, $base->toArray());
        //validate status of error model
        $response->assertStatus(422);
        //validate structure of error
        $response->assertJsonStructure($this->errorStructure);
        //validate message of field
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * Test create duplicate name Process Category
     */
    public function testCreateDuplicateName()
    {
        $route = route($this->resource . '.store');

        //create process category
        $name = 'Some name';
        factory(ProcessCategory::class)->create(['name' => $name]);

        $base = factory(ProcessCategory::class)->make(['name' => $name]);
        $response = $this->json('POST', $route, $base->toArray());
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * Test to verify our process categories listing api endpoint works without any filters
     */
    public function testProcessesListing()
    {
        $initialCount = ProcessCategory::count();
        // Create some processes
        $countProcesses = 20;
        factory(ProcessCategory::class, $countProcesses)->create();
        //Get a page of processes
        $page = 2;
        $perPage = 10;

        $route = route($this->resource . '.index');
        $response = $this->json('GET', $route . '?page=' . $page . '&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'total' => $initialCount + $countProcesses,
            'count' => $perPage,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil(($initialCount + $countProcesses) / $perPage),
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Test to verify our processes categories listing API endpoint works without any filters
     */
    public function testFiltering()
    {
        $perPage = 10;
        $initialActiveCount = ProcessCategory::where('status','ACTIVE')->count();
        $initialInactiveCount = ProcessCategory::where('status','INACTIVE')->count();

        // Create some processes
        $processActive = [
            'num' => 10,
            'status' => 'ACTIVE'
        ];
        $processInactive = [
            'num' => 15,
            'status' => 'INACTIVE'
        ];
        factory(ProcessCategory::class, $processActive['num'])->create(['status' => $processActive['status']]);
        factory(ProcessCategory::class, $processInactive['num'])->create(['status' => $processInactive['status']]);

        //Get active processes
        $route = route($this->resource . '.index');
        $response = $this->json('GET', $route . '?filter=ACTIVE&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset( [
            'total' => $initialActiveCount + $processActive['num'],
            'count' => $perPage,
            'per_page' => $perPage,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);

        //Get inactive processes
        $response = $this->json('GET', $route . '?filter=INACTIVE&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset( [
            'total' => $initialInactiveCount + $processInactive['num'],
            'count' => $perPage,
            'per_page' => $perPage,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Test to verify our process categories listing api endpoint works with sorting
     */
    public function testSorting()
    {
        // Create some processes
        factory(ProcessCategory::class)->create([
            'name' => 'aaaaaa'
        ]);
        factory(ProcessCategory::class)->create([
            'name' => 'zzzzz'
        ]);

        //Test the list sorted by name returns as first row {"name": "aaaaaa"}
        $route = route($this->resource . '.index');
        $response = $this->json('GET', $route . '?order_by=name&order_direction=asc');
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);

        $firstRow = $this->getDataAttributes($data[0]);
        $this->assertArraySubset([
            'name' => 'aaaaaa'
        ], $firstRow);


        //Test the list sorted desc returns as first row {"name": "zzzzz"}
        $response = $this->json('GET', $route . '?order_by=name&order_direction=DESC');
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        //Verify the data size
        $this->assertCount($meta['count'], $data);

        $firstRow = $this->getDataAttributes($data[0]);
        $this->assertArraySubset([
            'name' => 'zzzzz'
        ], $firstRow);
    }

    /**
     * Test pagination of process list
     *
     */
    public function testPagination()
    {
        // Number of processes in the tables at the moment of starting the test
        $initialRows = ProcessCategory::all()->count();

        // Number of rows to be created for the test
        $rowsToAdd = 7;

        // Now we create the specified number of processes
        factory(ProcessCategory::class, $rowsToAdd)->create();

        // The first page should have 5 items;
        $response = $this->json('GET', route($this->resource . '.index', ['per_page' => 5, 'page' => 1]));
        $response->assertJsonCount(5, 'data');

        // The second page should have the modulus of 2+$initialRows
        $response = $this->json('GET', route($this->resource . '.index', ['per_page' => 5, 'page' => 2]));
        $response->assertJsonCount((2 + $initialRows) % 5, 'data');
    }



    /**
     * Test show process category
     */
    public function testShowProcessCategory()
    {
        //Create a new process category
        $category = factory(ProcessCategory::class)->create();

        //Test that is correctly displayed
        $route = route($this->resource . '.show', [$category->uuid_text]);
        $response = $this->json('GET', $route);
        $response->assertStatus(200);
        $response->assertJsonStructure($this->structure);
    }

    /*
    + Test update process category
     */
    public function testUpdateProcess()
    {
        $item = factory(ProcessCategory::class)->create();

        $route = route($this->resource . '.update', [$item->uuid_text]);
        $fields = [
            'name' => $this->faker->name,
        ];
        $response = $this->json('PUT', $route, $fields);
        //validate status
        $response->assertStatus(200);
        //validate structure
        $response->assertJsonStructure($this->structure);
        //validate data
        $this->assertArraySubset($fields, $response->json());
    }

    /*
    + Test change status
     */
    public function testChangeStatus()
    {
        $item = factory(ProcessCategory::class)->create(['status' => 'ACTIVE']);

        $route = route($this->resource . '.update', [$item->uuid_text]);
        $fields = [
            'status' => 'INACTIVE',
        ];
        $response = $this->json('PUT', $route, $fields);
        //validate status
        $response->assertStatus(200);
        //validate structure
        $response->assertJsonStructure($this->structure);
        //validate data
        $this->assertArraySubset($fields, $response->json());
    }

    /*
    + Test validate name required
     */
    public function testValidateNameNotNull()
    {
        $item = factory(ProcessCategory::class)->create();

        $route = route($this->resource . '.update', [$item->uuid_text]);
        $fields = [
            'name' => null,
        ];
        $response = $this->json('PUT', $route, $fields);
        //validate status
        $response->assertStatus(422);
        //validate structure
        $response->assertJsonStructure(['errors' => ['name']]);

    }

    /*
    + Test validate name unique
     */
    public function testValidateNameUnique()
    {
        $name = 'Some name';
        factory(ProcessCategory::class)->create(['name' => $name]);
        $item = factory(ProcessCategory::class)->create();

        $route = route($this->resource . '.update', [$item->uuid_text]);
        $fields = [
            'name' => $name,
        ];
        $response = $this->json('PUT', $route, $fields);
        //validate status
        $response->assertStatus(422);
        //validate structure
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /*
    + Test validate data valid for status
     */
    public function testValidateStatus()
    {
        $item = factory(ProcessCategory::class)->create();

        $route = route($this->resource . '.update', [$item->uuid_text]);
        $fields = [
            'status' => 'NOT_EXISTS',
        ];
        $response = $this->json('PUT', $route, $fields);
        //validate status
        $response->assertStatus(422);
        //validate structure
        $response->assertJsonStructure(['errors' => ['status']]);
    }

    /**
     * Delete process category
     */
    public function testDeleteProcessCategory()
    {
        $processCategory = factory(ProcessCategory::class)->create();
        $route = route($this->resource . '.destroy', [$processCategory->uuid_text]);
        $response = $this->json('DELETE', $route);
        //validate status
        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
    }

    /**
     * test can not delete the category because you have assigned processes
     */
    public function testDeleteFailProcessCategory()
    {
        $processCategory = factory(Process::class)->create();
        $route = route($this->resource . '.destroy', [$processCategory->process_category_uuid_text]);
        $response = $this->json('DELETE', $route);
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => ['processes']]);
    }
}
