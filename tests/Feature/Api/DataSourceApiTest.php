<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Crypt;
use ProcessMaker\Models\DataSource;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

class DataSourceApiTest extends TestCase
{

    use RequestHelper;
    use WithFaker;
    use ResourceAssertionsTrait;

    protected $resource = 'api.datasources';

    protected $structure = [
        'name',
        'description',
        'endpoints',
        'mappings',
        'authtype',
        'credentials',
        'status',
        'data_source_category_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Basic test to get a list of sources
     *
     * @return void
     */
    public function testAuthGetAllSources()
    {

        $response = $this->get(route($this->resource . '.index'));

        //$response->assertUnauthorized();
        $response->assertStatus(302);

    }

    public function testGetAllSources()
    {
        // Basic listing assertions
        $response = $this->apiCall('GET', route($this->resource . '.index'));

        // Validate the header status code
        $response->assertSuccessful();
    }

    /**
     * Test the creation of DataSources
     */
    public function testCreateDataSource()
    {
        //Create a Data Source
        $route = route($this->resource . '.store');
        $dataSource = factory(DataSource::class)->make();
        $base = $dataSource->toArray();

        $response = $this->apiCall('POST', $route, $base);

        //validate status create
        $response->assertStatus(201);
        //validate structure
        $response->assertJsonStructure($this->structure);

        //Validate the correct information of the sent data
        $data = $response->json();
        $data['credentials'] = Crypt::decrypt($data['credentials']);
        $this->assertArraySubset($base, $data);
    }

    /**
     * Test the required fields
     */
    public function testCreateNameRequired()
    {
        $route = route($this->resource . '.store');
        $base = factory(DataSource::class)->make(['name' => null]);
        $response = $this->apiCall('POST', $route, $base->toArray());
        //validate status of error model
        $response->assertStatus(422);
        //validate structure of error
        $response->assertJsonStructure($this->errorStructure);
        //validate message of field
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * Test create duplicate name of Data Source
     */
    public function testCreateDuplicateName()
    {
        $route = route($this->resource . '.store');

        //create Data Source category
        $name = 'Some name';
        factory(DataSource::class)->create(['name' => $name]);

        $base = factory(DataSource::class)->make(['name' => $name]);
        $response = $this->apiCall('POST', $route, $base->toArray());
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * Test to verify our Data Source  listing api endpoint works without any filters
     */
    public function testListing()
    {
        $initialCount = DataSource::count();
        // Create some DataSources
        $count = 20;
        factory(DataSource::class, $count)->create();
        //Get a page of DataSources
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
            'total' => $initialCount + $count,
            'count' => $perPage,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil(($initialCount + $count) / $perPage),
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Test API filters
     */
    public function testFiltering()
    {
        $perPage = 10;
        $initialActiveCount = DataSource::where('authtype', 'like', 'NONE')->count();
        $initialInactiveCount = DataSource::where('authtype', 'like', 'BASIC')->count();

        // Create some DataSources
        $dataSourceCat1 = [
            'num' => 10,
            'authtype' => 'NONE'
        ];
        $dataSourceCat2 = [
            'num' => 15,
            'authtype' => 'BASIC'
        ];
        factory(DataSource::class, $dataSourceCat1['num'])->create(['authtype' => $dataSourceCat1['authtype']]);
        factory(DataSource::class, $dataSourceCat2['num'])->create(['authtype' => $dataSourceCat2['authtype']]);

        //Get active DataSources
        $route = route($this->resource . '.index');
        $response = $this->apiCall('GET', $route . '?filter=NONE&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'total' => $initialActiveCount + $dataSourceCat1['num'],
            'count' => $perPage,
            'per_page' => $perPage,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);

        //Get inactive DataSources
        $response = $this->apiCall('GET', $route . '?filter=BASIC&per_page=' . $perPage);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset([
            'total' => $initialInactiveCount + $dataSourceCat2['num'],
            'count' => $perPage,
            'per_page' => $perPage,
        ], $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
    }

    /**
     * Test to verify our Data Source  listing api endpoint works with sorting
     */
    public function testSorting()
    {
        // Create some DataSources
        factory(DataSource::class)->create([
            'name' => 'aaaaaa'
        ]);
        factory(DataSource::class)->create([
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
     * Test pagination of Data Source list
     *
     */
    public function testPagination()
    {
        // Number of DataSources in the tables at the moment of starting the test
        $initialRows = DataSource::all()->count();

        // Number of rows to be created for the test
        $rowsToAdd = 7;

        // Now we create the specified number of DataSources
        factory(DataSource::class, $rowsToAdd)->create();

        // The first page should have 5 items;
        $response = $this->apiCall('GET', route($this->resource . '.index', ['per_page' => 5, 'page' => 1]));
        $response->assertJsonCount(5, 'data');

        // The second page should have the modulus of 2+$initialRows
        $response = $this->apiCall('GET', route($this->resource . '.index', ['per_page' => 5, 'page' => 2]));
        $response->assertJsonCount((2 + $initialRows) % 5, 'data');
    }


    /**
     * Test show Data Source category
     */
    public function testShowDataSource()
    {
        //Create a new Data Source category
        $datasource = factory(DataSource::class)->create();

        //Test that is correctly displayed
        $route = route($this->resource . '.show', [$datasource->id]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
        $response->assertJsonStructure($this->structure);
    }

    /**
     * Test update Data Source
     */
    public function testUpdateDataSource()
    {
        $item = factory(DataSource::class)->create();

        $route = route($this->resource . '.update', [$item->id]);
        $item->name = $this->faker->name;

        $response = $this->apiCall('PUT', $route, $item->toArray());
        //validate status
        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
    }

    /**
     * Test validate update name required
     */
    public function testValidateNameNotNull()
    {
        $item = factory(DataSource::class)->create();

        $route = route($this->resource . '.update', [$item->id]);
        $item->name = null;
        $response = $this->apiCall('PUT', $route, $item->toArray());
        //validate status
        $response->assertStatus(422);
        //validate structure
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /*
    + Test validate name is unique in update
     */
    public function testValidateUpdateNameUnique()
    {
        $name = 'Some name';
        factory(DataSource::class)->create(['name' => $name]);
        $item = factory(DataSource::class)->create();

        $route = route($this->resource . '.update', [$item->id]);
        $item->name = $name;
        $response = $this->apiCall('PUT', $route, $item->toArray());
        //validate status
        $this->assertStatus(422, $response);
        //validate structure
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * Test delete Data Source
     */
    public function testDeleteDataSource()
    {
        $dataSource = factory(DataSource::class)->create();
        $route = route($this->resource . '.destroy', [$dataSource->id]);
        $response = $this->apiCall('DELETE', $route);
        //validate status
        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
    }

}
