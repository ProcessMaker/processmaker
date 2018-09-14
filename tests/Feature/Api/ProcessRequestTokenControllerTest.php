<?php
namespace Tests\Feature\Api;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to tokens list and show
 * the creation, update and deletion are controller by the engine
 * and should not be changed by endpoints
 *
 * @group process_tests
 */
class ProcessRequestTokenControllerTest extends TestCase
{

    use DatabaseTransactions;
    use WithFaker;
    use ResourceAssertionsTrait;

    protected $user;
    protected $resource = 'requests.tokens';
    protected $structure = [
        'uuid',
        'process_request_uuid',
        'user_uuid',
        'element_uuid',
        'element_type',
        'definition',
        'status',
        'completed_at',
        'due_at',
        'initiated_at',
        'riskchanges_at',
        'updated_at',
        'created_at',
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
        $this->request = factory(ProcessRequest::class)->create();
    }

    /**
     * Test to get the list of tokens
     */
    public function testGetListOfTokens()
    {
        $request = factory(ProcessRequest::class)->create();
        // Create some tokens
        factory(ProcessRequestToken::class, 20)->create([
            'process_request_uuid' => $request->uuid
        ]);
        //Get a page of tokens
        $route = route($this->resource . '.index', [$request->uuid_text, 'per_page' => 10, 'page' => 2]);
        $response = $this->json('GET', $route);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
    }

    /**
     * Test the filtering getting active tokens
     */
    public function testFilteringGetActiveTokens()
    {
        $request = factory(ProcessRequest::class)->create();
        // Create some tokens
        factory(ProcessRequestToken::class, 10)->create([
            'status' => 'ACTIVE',
            'process_request_uuid' => $request->uuid
        ]);
        factory(ProcessRequestToken::class, 10)->create([
            'status' => 'CLOSED',
            'process_request_uuid' => $request->uuid
        ]);

        //Get active tokens
        $route = route($this->resource . '.index', [$request->uuid_text, 'per_page' => 10, 'filter' => 'ACTIVE']);
        $response = $this->json('GET', $route);
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
            'process_request_uuid' => $request->uuid
        ]);
        factory(ProcessRequestToken::class)->create([
            'completed_at' => Carbon::now(),
            'process_request_uuid' => $request->uuid
        ]);

        //List sorted by completed_at returns as first row {"completed_at": null}
        $route = route($this->resource . '.index', [$request->uuid_text, 'order_by' => 'completed_at', 'order_direction' => 'asc']);
        $response = $this->json('GET', $route);
        //Verify the status
        $response->assertStatus(200);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        //Verify the first row
        $firstRow = $response->json('data')[0];
        $this->assertArraySubset(['completed_at'=>null], $firstRow);
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
            'process_request_uuid' => $request->uuid
        ]);

        // Get the second page, should have 5 items
        $perPage = 5;
        $page = 2;
        $response = $this->json('GET', route($this->resource . '.index', [$request->uuid_text, 'per_page' => $perPage, 'page' => $page]));
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
    public function testShowToken()
    {
        $request = factory(ProcessRequest::class)->create();
        //Create a new process without category
        $token = factory(ProcessRequestToken::class)->create([
            'process_request_uuid' => $request->uuid
        ]);

        //Test that is correctly displayed
        $route = route($this->resource . '.show', [$request->uuid_text, $token->uuid_text]);
        $response = $this->json('GET', $route);
        //Check the status
        $response->assertStatus(200);
        //Check the structure
        $response->assertJsonStructure($this->structure);
    }

    /**
     * Test get a token including user child.
     *
     */
    public function testShowTokenWithUser()
    {
        $request = factory(ProcessRequest::class)->create();
        //Create a new process without category
        $token = factory(ProcessRequestToken::class)->create([
            'process_request_uuid' => $request->uuid
        ]);

        //Test that is correctly displayed
        $route = route($this->resource . '.show', [$request->uuid_text, $token->uuid_text, 'include' => 'user']);
        $response = $this->json('GET', $route);
        //Check the status
        $response->assertStatus(200);
        //Check the structure
        $response->assertJsonStructure($this->structure);
        $response->assertJsonStructure(['user'=>['uuid', 'email']]);
    }
}
