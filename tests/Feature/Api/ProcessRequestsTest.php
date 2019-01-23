<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\User;

/**
 * Tests routes related to processes / CRUD related methods
 *
 * @group process_tests
 */
class ProcessRequestsTest extends TestCase
{

    use RequestHelper;
    use WithFaker;
    
    public $withPermissions = true;

    const API_TEST_URL = '/requests';

    const STRUCTURE = [
        'id',
        'process_id',
        'process_collaboration_id',
        'user_id',
        'participant_id',
        'status',
        'name',
        'completed_at',
        'initiated_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Get a list of Requests without query parameters.
     */
    public function testListRequest()
    {
        ProcessRequest::query()->delete();

        factory(ProcessRequest::class, 10)->create();

        $response = $this->apiCall('GET', self::API_TEST_URL);

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
     * Test to verify that the list dates are in the correct format (yyyy-mm-dd H:i+GMT)
     */
    public function testScreenListDates()
    {
        $name = 'testRequestTimezone';
        $newEntity = factory(ProcessRequest::class)->create(['name' => $name]);
        $route = self::API_TEST_URL . '?filter=' . $name;
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
        $response = $this->apiCall('GET', self::API_TEST_URL . $query);

        //Validate the header status code
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        // Verify return data
        $this->assertEquals(1, $response->json()['meta']['total']);
        $this->assertEquals('name', $response->json()['meta']['sort_by']);
        $this->assertEquals('DESC', $response->json()['meta']['sort_order']);
    }

    /**
     * Test that paged values are returned as expected
     */
    public function testWithPagination()
    {
        $process = factory(Process::class)->create();
        factory(ProcessRequest::class, 5)->create([
            'name' => $process->name,
            'process_id' => $process->id,
        ]);
        $query = '?page=2&per_page=3&order_by=name';
        
        $response = $this->apiCall('GET', self::API_TEST_URL . $query);

        //Validate the header status code
        $response->assertStatus(200);
        $json = $response->json();

        // 2 items on 2nd page
        $this->assertCount(2, $json['data']);

        // keys should be re-indexed so they get converted to
        // arrays instead of objects on json_encode
        $this->assertEquals([0,1], array_keys($json['data']));
    }

    /**
     * Get a list of Request by type
     */
    public function testListRequestWithType()
    {
        $in_progress = factory(ProcessRequest::class)->create([
            'status' => 'ACTIVE',
            'user_id' => $this->user->id
        ]);

        $completed = factory(ProcessRequest::class)->create([
            'status' => 'COMPLETED',
            'user_id' => $this->user->id
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL . '/?type=completed');
        $json = $response->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals($completed->id, $json['data'][0]['id']);

        $response = $this->apiCall('GET', self::API_TEST_URL . '/?type=in_progress');
        $json = $response->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals($in_progress->id, $json['data'][0]['id']);
    }

    /**
     * Get a list of Request with assocations included
     */
    public function testListRequestWithIncludes()
    {
        $process = factory(Process::class)->create();

        factory(ProcessRequest::class)->create([
            'process_id' => $process->id,
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL . '/?include=process');
        $json = $response->json();
        $this->assertEquals($process->id, $json['data'][0]['process']['id']);
    }

    /**
     * Get a request
     */
    public function testGetRequest()
    {
        //get the id from the factory
        $request = factory(ProcessRequest::class)->create()->id;

        //load api
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $request);

        //Validate the status is correct
        $response->assertStatus(200);

        //verify structure
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Parameters required for update of request
     */
    public function testUpdateProcessRequestParametersRequired()
    {
        $id = factory(ProcessRequest::class)->create(['name' => 'mytestrequestname'])->id;
        //The post must have the required parameters
        $url = self::API_TEST_URL . '/' . $id;

        $response = $this->apiCall('PUT', $url, [
            'name' => null
        ]);

        //Validate the header status code
        $response->assertStatus(422);
    }


    /**
     * Update request in process
     */
    public function testUpdateProcessRequest()
    {
        $faker = Faker::create();

        $url = self::API_TEST_URL . '/' . factory(ProcessRequest::class)->create()->id;

        //Load the starting request data
        $verify = $this->apiCall('GET', $url);

        //Post saved success
        $response = $this->apiCall('PUT', $url, [
            'name' => $faker->unique()->name,
            'data' => '{"test":1}',
            'process_id' => json_decode($verify->getContent())->process_id
        ]);

        //Validate the header status code
        $response->assertStatus(204);

        //Load the updated request data
        $verify_new = $this->apiCall('GET', $url);

        //Check that it has changed
        $this->assertNotEquals($verify, $verify_new);
    }

    /**
     * Check that the validation wont allow duplicate requestnames
     */
    public function testUpdateProcessRequestTitleExists()
    {
        $request1 = factory(ProcessRequest::class)->create([
            'name' => 'MyRequestName',
        ]);

        $request2 = factory(ProcessRequest::class)->create();

        $url = self::API_TEST_URL . '/' . $request2->id;

        $response = $this->apiCall('PUT', $url, [
            'name' => 'MyRequestName',
        ]);
        //Validate the header status code
        $response->assertStatus(422);
        $response->assertSeeText('The name has already been taken');
    }

    /**
     * test to be sure that you cannot cancel a request until you have been given permission
     */
    public function testCancelRequestWithPermissions()
    {
        // We need an admin user and a non-admin user
        $admin = $this->user;
        $nonAdmin = factory(User::class)->create(['is_administrator' => false]);

        // Create a process and a process request       
        $process = factory(Process::class)->create();
        $request = factory(ProcessRequest::class)->create(['user_id' => $nonAdmin->id, 'process_id' => $process->id]);

        // Attempt to cancel a request
        $this->user = $nonAdmin;
        $route = route('api.requests.update', [$request->id]);
        $response = $this->apiCall('PUT', $route, [
                    'status' => 'CANCELED',
                ]);

        // Confirm the user does not have access
        $response->assertStatus(403);
    
        // Add the user to the list of users that can cancel
        $this->user = $admin;
        $route = route('api.processes.update', [$process->id]);
        $response = $this->apiCall('PUT', $route, [
            'name' => 'Update Process',
            'description' => 'Update Test',
            'cancel_request' => ['users' => [$nonAdmin->id], 'groups' => []]
        ]);
        
        // Assert that the API returned a valid response
        $response->assertStatus(200, $response);
        
        // Attempt to cancel the request
        $this->user = $nonAdmin;
        $route = route('api.requests.update', [$request->id]);
        $response = $this->apiCall('PUT', $route, [
            'status' => 'CANCELED',
        ]);
        
        // Assert that the API updated
        $response->assertStatus(204);
    }

    /**
     * Delete request in process
     */
    public function testDeleteProcessRequest()
    {
        //Remove request
        $url = self::API_TEST_URL . '/' . factory(ProcessRequest::class)->create()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(204);
    }

    /**
     * The request does not exist in process
     */
    public function testDeleteProcessRequestNotExist()
    {
        //ProcessRequest not exist
        $url = self::API_TEST_URL . '/' . factory(ProcessRequest::class)->make()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(405);
    }
}
