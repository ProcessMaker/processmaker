<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;


/**
 * Tests routes related to script / CRUD related methods
 *
 * @group script_tests
 */
class ScriptCategoriesTest extends TestCase
{

    use WithFaker;
    use ResourceAssertionsTrait;
    use RequestHelper;

    protected $resource = 'api.script_categories';
    protected $structure = [
        'id',
        'name',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * Test the creation of script
     */
    public function testCreateScriptCategory()
    {
        //Create a script category
        $route = route($this->resource . '.store');
        $base = factory(ScriptCategory::class)->make();
        $response = $this->apiCall('POST', $route, $base->toArray());
        //validate status create
        $response->assertStatus(201);
        //validate structure
        $response->assertJsonStructure($this->structure);
        //Validate the correct information of the sent data
        $this->assertArraySubset($base->toArray(), $response->json());
    }

    /**
     * Test the required fields
     */
    public function testCreateNameRequired()
    {
        $route = route($this->resource . '.store');
        $base = factory(ScriptCategory::class)->make(['name' => null]);
        $response = $this->apiCall('POST', $route, $base->toArray());
        //validate status of error model
        $response->assertStatus(422);
        //validate structure of error
        $response->assertJsonStructure($this->errorStructure);
        //validate message of field
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * Test create duplicate name Script Category
     */
    public function testCreateDuplicateName()
    {
        $route = route($this->resource . '.store');

        //create script category
        $name = 'Some name';
        factory(ScriptCategory::class)->create(['name' => $name]);

        $base = factory(ScriptCategory::class)->make(['name' => $name]);
        $response = $this->apiCall('POST', $route, $base->toArray());
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * Test to verify our script categories listing api endpoint works without any filters
     */
    public function testScriptsListing()
    {
        $initialCount = ScriptCategory::count();
        // Create some script
        $countScripts = 20;
        factory(ScriptCategory::class, $countScripts)->create();
        //Get a page of script
        $page = 2;
        $perPage = 10;

        $route = route($this->resource . '.index');
        $response = $this->apiCall('GET', $route . '?page=' . $page . '&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'total' => $initialCount + $countScripts,
            'count' => $perPage,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil(($initialCount + $countScripts) / $perPage),
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Test to verify our script categories listing API endpoint works without any filters
     */
    public function testFiltering()
    {
        $perPage = 10;
        $initialActiveCount = ScriptCategory::where('status','ACTIVE')->count();
        $initialInactiveCount = ScriptCategory::where('status','INACTIVE')->count();

        factory(ScriptCategory::class, 3)->create(['is_system' => true, 'status' => 'ACTIVE']);
        // Create some script
        $scriptActive = [
            'num' => 10,
            'status' => 'ACTIVE'
        ];
        $scriptInactive = [
            'num' => 15,
            'status' => 'INACTIVE'
        ];
        factory(ScriptCategory::class, $scriptActive['num'])->create(['status' => $scriptActive['status']]);
        factory(ScriptCategory::class, $scriptInactive['num'])->create(['status' => $scriptInactive['status']]);

        //Get active script
        $route = route($this->resource . '.index');
        $response = $this->apiCall('GET', $route . '?filter=ACTIVE&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset( [
            'total' => $initialActiveCount + $scriptActive['num'],
            'count' => $perPage,
            'per_page' => $perPage,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);

        //Get inactive script
        $response = $this->apiCall('GET', $route . '?filter=INACTIVE&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset( [
            'total' => $initialInactiveCount + $scriptInactive['num'],
            'count' => $perPage,
            'per_page' => $perPage,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Test list categories by status
     */
    public function testFilteringStatus()
    {
        $perPage = 10;
        $initialActiveCount = ScriptCategory::where('status','ACTIVE')->count();
        $initialInactiveCount = ScriptCategory::where('status','INACTIVE')->count();

        // Create some script
        $scriptActive = [
            'num' => 10,
            'status' => 'ACTIVE'
        ];
        $scriptInactive = [
            'num' => 15,
            'status' => 'INACTIVE'
        ];

        factory(ScriptCategory::class, $scriptActive['num'])->create(['status' => $scriptActive['status']]);
        factory(ScriptCategory::class, $scriptInactive['num'])->create(['status' => $scriptInactive['status']]);

        //Get active script
        $route = route($this->resource . '.index');
        $response = $this->apiCall('GET', $route . '?status=ACTIVE&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset( [
            'total' => $initialActiveCount + $scriptActive['num'],
            'count' => $perPage,
            'per_page' => $perPage,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Test to verify our script categories listing api endpoint works with sorting
     */
    public function testSorting()
    {
        // Create some script
        factory(ScriptCategory::class)->create([
            'name' => 'aaaaaa'
        ]);
        factory(ScriptCategory::class)->create([
            'name' => 'zzzzz'
        ]);

        //Test the list sorted by name returns as first row {"name": "aaaaaa"}
        $route = route($this->resource . '.index');
        $response = $this->apiCall('GET', $route . '?order_by=name&order_direction=asc');
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'count' => count($data)
        ], $meta);

        $firstRow = $this->getDataAttributes($data[0]);
        $this->assertArraySubset([
            'name' => 'aaaaaa'
        ], $firstRow);


        //Test the list sorted desc returns as first row {"name": "zzzzz"}
        $response = $this->apiCall('GET', $route . '?order_by=name&order_direction=DESC');
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
     * Test pagination of script list
     *
     */
    public function testPagination()
    {
        // Number of script in the tables at the moment of starting the test
        $initialRows = ScriptCategory::all()->count();

        // Number of rows to be created for the test
        $rowsToAdd = 7;

        // Now we create the specified number of script
        factory(ScriptCategory::class, $rowsToAdd)->create();

        // The first page should have 5 items;
        $response = $this->apiCall('GET', route($this->resource . '.index', ['per_page' => 5, 'page' => 1]));
        $response->assertJsonCount(5, 'data');

        // The second page should have the modulus of 2+$initialRows
        $response = $this->apiCall('GET', route($this->resource . '.index', ['per_page' => 5, 'page' => 2]));
        $response->assertJsonCount((2 + $initialRows) % 5, 'data');
    }



    /**
     * Test show script category
     */
    public function testShowScriptCategory()
    {
        //Create a new script category
        $category = factory(ScriptCategory::class)->create();

        //Test that is correctly displayed
        $route = route($this->resource . '.show', [$category->id]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
        $response->assertJsonStructure($this->structure);
    }

    /*
    + Test update script category
     */
    public function testUpdateScript()
    {
        $item = factory(ScriptCategory::class)->create();

        $route = route($this->resource . '.update', [$item->id]);
        $fields = [
            'name' => $this->faker->name,
            'status' => 'ACTIVE',
        ];
        $response = $this->apiCall('PUT', $route, $fields);
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
        $item = factory(ScriptCategory::class)->create(['status' => 'ACTIVE']);

        $route = route($this->resource . '.update', [$item->id]);
        $fields = [
            'name' => 'test',
            'status' => 'INACTIVE',
        ];
        $response = $this->apiCall('PUT', $route, $fields);
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
        $item = factory(ScriptCategory::class)->create();

        $route = route($this->resource . '.update', [$item->id]);
        $fields = [
            'name' => null,
            'status' => 'ACTIVE',
        ];
        $response = $this->apiCall('PUT', $route, $fields);
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
        factory(ScriptCategory::class)->create(['name' => $name]);
        $item = factory(ScriptCategory::class)->create();

        $route = route($this->resource . '.update', [$item->id]);
        $fields = [
            'name' => $name,
            'status' => 'ACTIVE',
        ];
        $response = $this->apiCall('PUT', $route, $fields);
        //validate status
        $this->assertStatus(422, $response);
        //validate structure
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * Test validate data valid for status
     */
    public function testValidateStatus()
    {
        $item = factory(ScriptCategory::class)->create();

        $route = route($this->resource . '.update', [$item->id]);
        $fields = [
            'status' => 'NOT_EXISTS',
        ];
        $response = $this->apiCall('PUT', $route, $fields);
        //validate status
        $response->assertStatus(422);
        //validate structure
        $response->assertJsonStructure(['errors' => ['status']]);
    }

    /**
     * Delete script category
     */
    public function testDeleteScriptCategory()
    {
        $scriptCategory = factory(ScriptCategory::class)->create();
        $route = route($this->resource . '.destroy', [$scriptCategory->id]);
        $response = $this->apiCall('DELETE', $route);
        //validate status
        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
    }

    /**
     * test can not delete the category because you have assigned script
     */
    public function testDeleteFailScriptCategory()
    {
        $script = factory(Script::class)->create();
        $route = route($this->resource . '.destroy', [$script->script_category_id]);
        $response = $this->apiCall('DELETE', $route);
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => ['scripts']]);
    }

}
