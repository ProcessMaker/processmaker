<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

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
            'process_request_id' => $request->id
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
     * Test to verify that the list dates are in the correct format (yyyy-mm-dd H:i+GMT)
     */
    public function testTaskListDates()
    {
        $name = 'testTaskTimezone';
        $request = factory(ProcessRequest::class)->create(['name' => $name]);
        // Create some tokens
        $newEntity = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $request->id
        ]);
        $route = route('api.' . $this->resource . '.index', ['filter' => $name]);
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
            'process_request_id' => $request->id
        ]);
        factory(ProcessRequestToken::class, 10)->create([
            'status' => 'CLOSED',
            'process_request_id' => $request->id
        ]);

        //Get active tokens
        $route = route('api.' . $this->resource . '.index', ['per_page' => 10, 'filter' => 'ACTIVE']);
        $response = $this->apiCall('GET', $route);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
    }

    /**
     * Test list of tokens sorting by completed_at
     */
    public function testSorting()
    {
        $request = factory(ProcessRequest::class)->create();
        // Create some tokens
        factory(ProcessRequestToken::class)->create([
            'completed_at' => null,
            'process_request_id' => $request->id
        ]);
        factory(ProcessRequestToken::class)->create([
            'completed_at' => Carbon::now(),
            'process_request_id' => $request->id
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

    /**
     * Test pagination of tokens list
     *
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
            'process_request_id' => $request->id
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
     *
     */
    public function testShowTask()
    {
        $request = factory(ProcessRequest::class)->create();
        //Create a new process without category
        $token = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $request->id
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
     *
     */
    public function testShowTaskWithUser()
    {
        $request = factory(ProcessRequest::class)->create();
        //Create a new process without category
        $token = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $request->id
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
}
