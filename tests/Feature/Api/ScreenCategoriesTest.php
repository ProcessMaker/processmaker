<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to screens / CRUD related methods
 *
 * @group screen_tests
 */
class ScreenCategoriesTest extends TestCase
{
    use WithFaker;
    use ResourceAssertionsTrait;
    use RequestHelper;

    protected $resource = 'api.screen_categories';

    protected $structure = [
        'id',
        'name',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * Test the creation of screens
     */
    public function testCreateScreenCategory()
    {
        //Create a screen category
        $route = route($this->resource . '.store');
        $base = ScreenCategory::factory()->make();
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
        $base = ScreenCategory::factory()->make(['name' => null]);
        $response = $this->apiCall('POST', $route, $base->toArray());
        //validate status of error model
        $response->assertStatus(422);
        //validate structure of error
        $response->assertJsonStructure($this->errorStructure);
        //validate message of field
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * Test create duplicate name Screen Category
     */
    public function testCreateDuplicateName()
    {
        $route = route($this->resource . '.store');

        //create screen category
        $name = 'Some name';
        ScreenCategory::factory()->create(['name' => $name]);

        $base = ScreenCategory::factory()->make(['name' => $name]);
        $response = $this->apiCall('POST', $route, $base->toArray());
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * Test to verify our screen categories listing api endpoint works without any filters
     */
    public function testScreensListing()
    {
        $initialCount = ScreenCategory::nonSystem()->count();
        // Create some screens
        $countScreens = 20;
        ScreenCategory::factory()->count($countScreens)->create();
        //Get a page of screens
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
            'total' => $initialCount + $countScreens,
            'count' => $perPage,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil(($initialCount + $countScreens) / $perPage),
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Test to verify our screens categories listing API endpoint works without any filters
     */
    public function testFiltering()
    {
        $perPage = 10;
        $initialInactiveCount = ScreenCategory::nonSystem()->where('status', 'INACTIVE')->count();

        ScreenCategory::factory()->count(3)->create(['is_system' => true, 'status' => 'ACTIVE']);
        // Create some screens
        $screenActive = [
            'num' => 10,
            'status' => 'ACTIVE',
        ];
        $screenInactive = [
            'num' => 15,
            'status' => 'INACTIVE',
        ];
        ScreenCategory::factory()->count($screenActive['num'])->create(['status' => $screenActive['status']]);
        ScreenCategory::factory()->count($screenInactive['num'])->create(['status' => $screenInactive['status']]);

        $name = 'Script search';
        ScreenCategory::factory()->create(['status' => 'ACTIVE', 'name' => $name]);

        //Get active screens
        $route = route($this->resource . '.index');
        $response = $this->apiCall('GET', $route . '?filter=' . $name . '&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'total' => 1,
            'count' => 1,
            'per_page' => $perPage,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);

        //Get inactive screens
        $response = $this->apiCall('GET', $route . '?filter=INACTIVE&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'total' => $initialInactiveCount + $screenInactive['num'],
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
        $initialActiveCount = ScreenCategory::nonSystem()->where('status', 'ACTIVE')->count();

        // Create some screens
        $screenActive = [
            'num' => 10,
            'status' => 'ACTIVE',
        ];
        $screenInactive = [
            'num' => 15,
            'status' => 'INACTIVE',
        ];

        ScreenCategory::factory()->count($screenActive['num'])->create(['status' => $screenActive['status']]);
        ScreenCategory::factory()->count($screenInactive['num'])->create(['status' => $screenInactive['status']]);

        //Get active screens
        $route = route($this->resource . '.index');
        $response = $this->apiCall('GET', $route . '?status=ACTIVE&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'total' => $initialActiveCount + $screenActive['num'],
            'count' => $perPage,
            'per_page' => $perPage,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Test to verify our screen categories listing api endpoint works with sorting
     */
    public function testSorting()
    {
        // Create some screens
        ScreenCategory::factory()->create([
            'name' => 'aaaaaa',
        ]);
        ScreenCategory::factory()->create([
            'name' => 'zzzzz',
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
            'count' => count($data),
        ], $meta);

        $firstRow = $this->getDataAttributes($data[0]);
        $this->assertArraySubset([
            'name' => 'aaaaaa',
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
            'name' => 'zzzzz',
        ], $firstRow);
    }

    /**
     * Test pagination of screen list
     */
    public function testPagination()
    {
        // Number of screens in the tables at the moment of starting the test
        $initialRows = ScreenCategory::nonSystem()->get()->count();

        // Number of rows to be created for the test
        $rowsToAdd = 7;

        // Now we create the specified number of screens
        ScreenCategory::factory()->count($rowsToAdd)->create();

        // The first page should have 5 items;
        $response = $this->apiCall('GET', route($this->resource . '.index', ['per_page' => 5, 'page' => 1]));
        $response->assertJsonCount(5, 'data');

        // The second page should have the modulus of 2+$initialRows
        $response = $this->apiCall('GET', route($this->resource . '.index', ['per_page' => 5, 'page' => 2]));
        $response->assertJsonCount((2 + $initialRows) % 5, 'data');
    }

    /**
     * Test show screen category
     */
    public function testShowScreenCategory()
    {
        //Create a new screen category
        $category = ScreenCategory::factory()->create();

        //Test that is correctly displayed
        $route = route($this->resource . '.show', [$category->id]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
        $response->assertJsonStructure($this->structure);
    }

    /*
    + Test update screen category
     */
    public function testUpdateScreen()
    {
        $item = ScreenCategory::factory()->create();

        $route = route($this->resource . '.update', [$item->id]);
        $fields = [
            'name' => $this->faker->name(),
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
        $item = ScreenCategory::factory()->create(['status' => 'ACTIVE']);

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
        $item = ScreenCategory::factory()->create();

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
        ScreenCategory::factory()->create(['name' => $name]);
        $item = ScreenCategory::factory()->create();

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
        $item = ScreenCategory::factory()->create();

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
     * Delete screen category
     */
    public function testDeleteScreenCategory()
    {
        $screenCategory = ScreenCategory::factory()->create();
        $route = route($this->resource . '.destroy', [$screenCategory->id]);
        $response = $this->apiCall('DELETE', $route);
        //validate status
        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
    }

    /**
     * test can not delete the category because you have assigned screens
     */
    public function testDeleteFailScreenCategory()
    {
        $screen = Screen::factory()->create();
        $route = route($this->resource . '.destroy', [$screen->screen_category_id]);
        $response = $this->apiCall('DELETE', $route);
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => ['screens']]);
    }
}
