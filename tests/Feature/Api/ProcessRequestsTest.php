<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Http\Controllers\Api\ProcessRequestController;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

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
        'updated_at',
    ];

    /**
     * Get a list of Requests without query parameters.
     */
    public function testListRequest()
    {
        ProcessRequest::query()->delete();

        ProcessRequest::factory()->count(10)->create();

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
     * Count the total of request per process
     */
    public function testCountRequest()
    {
        ProcessRequest::query()->delete();
        $process = Process::factory()->create();
        ProcessRequest::factory()->count(10)->create([
            'process_id' => $process->id,
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $process->id . '/count');

        // Validate the header status code
        $response->assertStatus(200);

        // Verify structure
        $response->assertJsonStructure([
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
        $newEntity = ProcessRequest::factory()->create(['name' => $name]);
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
    public function testListRequestIncludingData()
    {
        $requestname = 'mytestrequestnameincludesdata';

        //Create requests with data
        ProcessRequest::factory()->create([
            'name' => $requestname,
            'data' => ['test' => 'value1'],
        ]);

        //Set direction to ascending
        $query = "?page=1&include=data&order_by=data.test&order_direction=ASC&filter=$requestname";
        $response = $this->apiCall('GET', self::API_TEST_URL . $query);

        $response->assertStatus(200);
        $response->assertJsonFragment(['data' => ['test' => 'value1']]);
    }

    /**
     * Get a list of Request with parameters
     */
    public function testListRequestOrderByData()
    {
        $requestname = 'mytestrequestnameorderbydata';

        //Create requests with data
        ProcessRequest::factory()->create([
            'name' => $requestname,
            'data' => ['test' => 'value1'],
        ]);

        ProcessRequest::factory()->create([
            'name' => $requestname,
            'data' => ['test' => 'value2'],
        ]);

        //Set direction to ascending
        $query = "?page=1&include=data&order_by=data.test&order_direction=ASC&filter=$requestname";
        $response = $this->apiCall('GET', self::API_TEST_URL . $query);

        //Verify that the request with test data of "value1" is first
        $response->assertStatus(200);
        $this->assertEquals('value1', $response->json()['data'][0]['data']['test']);

        //Set direction to descending
        $query = "?page=1&include=data&order_by=data.test&order_direction=DESC&filter=$requestname";
        $response = $this->apiCall('GET', self::API_TEST_URL . $query);

        //Verify that the request with test data of "value2" is first
        $response->assertStatus(200);
        $this->assertEquals('value2', $response->json()['data'][0]['data']['test']);
    }

    /**
     * Get a list of Request with parameters
     */
    public function testListRequestWithQueryParameter()
    {
        $requestname = 'mytestrequestname';

        ProcessRequest::factory()->create([
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
     * Test that we can filter by participant
     */
    public function testFilterByParticipant()
    {
        $participant = User::factory()->create();
        $otherUser = User::factory()->create();

        $request = ProcessRequest::factory()->create(['status' => 'ACTIVE']);
        $otherRequest = ProcessRequest::factory()->create(['status' => 'ACTIVE']);

        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'user_id' => $participant->id,
        ]);

        $otherToken = ProcessRequestToken::factory()->create([
            'process_request_id' => $otherRequest->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL, ['pmql' => "participant = \"{$participant->username}\""]);
        $this->assertEquals(1, $response->json()['meta']['total']);
        $this->assertEquals($request->id, $response->json()['data'][0]['id']);
    }

    /**
     * Test that paged values are returned as expected
     */
    public function testWithPagination()
    {
        $process = Process::factory()->create();
        ProcessRequest::factory()->count(5)->create([
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
        $this->assertEquals([0, 1], array_keys($json['data']));
    }

    /**
     * Get a list of Request by type
     */
    public function testListRequestWithType()
    {
        $in_progress = ProcessRequest::factory()->create([
            'status' => 'ACTIVE',
            'user_id' => $this->user->id,
        ]);

        $completed = ProcessRequest::factory()->create([
            'status' => 'COMPLETED',
            'user_id' => $this->user->id,
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
        $process = Process::factory()->create();

        ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL . '/?include=process');
        $json = $response->json();
        $this->assertEquals($process->id, $json['data'][0]['process']['id']);
    }

    /**
     * Get a list of requests with a user that has view all permission
     */
    public function testListRequestViewAllPermission()
    {
        $this->user = User::factory()->create(['status'=>'ACTIVE']);
        $processRequest = ProcessRequest::factory()->create([]);

        $response = $this->apiCall('GET', self::API_TEST_URL);
        $json = $response->json();
        $this->assertCount(0, $json['data']);

        $this->user->giveDirectPermission('view-all_requests');
        $this->user->refresh();

        $response = $this->apiCall('GET', self::API_TEST_URL);
        $json = $response->json();
        $this->assertEquals($processRequest->id, $json['data'][0]['id']);
    }

    /**
     * Get a request
     */
    public function testGetRequest()
    {
        //get the id from the factory
        $request = ProcessRequest::factory()->create()->id;

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
        $id = ProcessRequest::factory()->create(['name' => 'mytestrequestname'])->id;
        //The post must have the required parameters
        $url = self::API_TEST_URL . '/' . $id;

        $response = $this->apiCall('PUT', $url, [
            'name' => null,
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

        $url = self::API_TEST_URL . '/' . ProcessRequest::factory()->create()->id;

        //Load the starting request data
        $verify = $this->apiCall('GET', $url);

        //Post saved success
        $response = $this->apiCall('PUT', $url, [
            'name' => $faker->unique()->name(),
            'data' => ['test' => 1],
            'process_id' => json_decode($verify->getContent())->process_id,
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
        $request1 = ProcessRequest::factory()->create([
            'name' => 'MyRequestName',
        ]);

        $request2 = ProcessRequest::factory()->create();

        $url = self::API_TEST_URL . '/' . $request2->id;

        $response = $this->apiCall('PUT', $url, [
            'name' => 'MyRequestName',
        ]);
        //Validate the header status code
        $response->assertStatus(422);
        $response->assertSeeText('The Name has already been taken');
    }

    /**
     * test to be sure that you cannot cancel a request until you have been given permission
     */
    public function testCancelRequestWithPermissions()
    {
        // We need an admin user and a non-admin user
        $admin = $this->user;

        $nonAdmin = User::factory()->create([
            'is_administrator' => false,
        ]);

        // Create a single process in order to create
        // two process requests with the same process
        $process = Process::factory()->create([
            'user_id' => $admin->id,
        ]);

        // Create the initial process request
        $initialProcessVersionRequest = ProcessRequest::factory()->create([
            'user_id' => $nonAdmin->id,
            'process_id' => $process->id,
        ]);

        // Attempt to cancel a request
        $this->user = $nonAdmin;
        $route = route('api.requests.update', [$initialProcessVersionRequest->id]);
        $response = $this->apiCall('PUT', $route, ['status' => 'CANCELED']);

        // Confirm the user does not have access
        $response->assertStatus(403);

        // Add the user to the list of users that can cancel
        $this->user = $admin;
        $route = route('api.processes.update', [$process->id]);
        $response = $this->apiCall('PUT', $route, [
            'name' => 'Update Process',
            'description' => 'Update Test',
            'cancel_request' => ['users' => [$nonAdmin->id], 'groups' => []],
        ]);

        // Create a second process request with the
        // same process, this time the process request
        // will honor the new process configuration
        $secondProcessVersionRequest = ProcessRequest::factory()->create([
            'user_id' => $nonAdmin->id,
            'process_id' => $process->id,
        ]);

        // Attempt to cancel a request
        $this->user = $nonAdmin;
        $route = route('api.requests.update', [$secondProcessVersionRequest->id]);
        $response = $this->apiCall('PUT', $route, ['status' => 'CANCELED']);

        // Assert that the API updated
        $response->assertStatus(204);
    }

    /**
     * Test ability to complete a request if it has the status: ERROR
     */
    public function testCompleteRequest()
    {
        $this->user->is_administrator = false;
        $this->user->saveOrFail();
        $request = ProcessRequest::factory()->create(['status' => 'ACTIVE']);

        // give the user editData permission to get past the route check
        $request->processVersion->usersCanEditData()->sync([
            $this->user->id => [
                'method' => 'EDIT_DATA',
                'process_id' => $request->process->id,
            ],
        ]);

        $route = route('api.requests.update', [$request->id]);
        $response = $this->apiCall('PUT', $route, ['status' => 'COMPLETED']);

        // Confirm the user does not have access
        $response->assertStatus(403);
        $this->assertEquals('Not authorized to complete this request.', $response->json()['message']);

        // Make user admin again
        $this->user->is_administrator = true;
        $this->user->saveOrFail();
        $response = $this->apiCall('PUT', $route, ['status' => 'COMPLETED']);

        // Confirm only ERROR status can be changed
        $response->assertStatus(422);
        $this->assertEquals('Only requests with status: ERROR can be manually completed',
            $response->json()['errors']['status'][0]
        );

        $request->status = 'ERROR';
        $request->saveOrFail();

        $response = $this->apiCall('PUT', $route, ['status' => 'COMPLETED']);
        $response->assertStatus(204);

        $request->refresh();
        $this->assertEquals('COMPLETED', $request->status);

        // Verify comment added
        $this->assertEquals(
            $this->user->fullname . ' manually completed the request from an error state',
            Comment::first()->body
        );

        // Verify metadata was removed from data object
        $this->assertFalse(array_key_exists('_user', $request->data));
    }

    /**
     * Delete request and request tokens in parent process, and children processes
     */
    public function testDeleteParentProcessRequestShouldRemoveRequestAndTokensForParentAndChildren()
    {
        $user = User::factory()->create();
        // Prepare data
        $parentProcessRequest = ProcessRequest::factory()->create(['status' => 'ACTIVE']);

        $childProcessRequest1 = ProcessRequest::factory()->create([
            'status' => 'ACTIVE',
            'parent_request_id' => $parentProcessRequest->id,
        ]);

        $childProcessRequest2 = ProcessRequest::factory()->create([
            'status' => 'ACTIVE',
            'parent_request_id' => $childProcessRequest1->id,
        ]);

        $parentTokens = ProcessRequestToken::factory()->count(3)->create([
            'process_request_id' => $parentProcessRequest->id,
        ]);

        $childTokens1 = ProcessRequestToken::factory()->count(4)->create([
            'process_request_id' => $childProcessRequest1->id,
        ]);

        $childTokens2 = ProcessRequestToken::factory()->count(5)->create([
            'process_request_id' => $childProcessRequest2->id,
        ]);

        // Assert database has parent and child requests
        $this->assertEquals(1, ProcessRequest::where('id', $parentProcessRequest->id)->count());
        $this->assertEquals(1, ProcessRequest::where('id', $childProcessRequest1->id)->count());
        $this->assertEquals(1, ProcessRequest::where('id', $childProcessRequest2->id)->count());

        // Assert count database has tokens for parent and child requests
        $this->assertEquals(3, ProcessRequestToken::where('process_request_id', $parentProcessRequest->id)->count());
        $this->assertEquals(4, ProcessRequestToken::where('process_request_id', $childProcessRequest1->id)->count());
        $this->assertEquals(5, ProcessRequestToken::where('process_request_id', $childProcessRequest2->id)->count());

        //Remove request
        $url = self::API_TEST_URL . '/' . $parentProcessRequest->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(204);

        // Assert database does not has parent and child requests
        $this->assertEquals(0, ProcessRequest::where('id', $parentProcessRequest->id)->count());
        $this->assertEquals(0, ProcessRequest::where('id', $childProcessRequest1->id)->count());
        $this->assertEquals(0, ProcessRequest::where('id', $childProcessRequest2->id)->count());

        // Assert database does not has parent and child requests tokens
        $this->assertEquals(0, ProcessRequestToken::where('process_request_id', $parentProcessRequest->id)->count());
        $this->assertEquals(0, ProcessRequestToken::where('process_request_id', $childProcessRequest1->id)->count());
        $this->assertEquals(0, ProcessRequestToken::where('process_request_id', $childProcessRequest2->id)->count());
    }

    /**
     * Delete request and request tokens in subprocess and child process
     */
    public function testDeleteChildProcessRequestShouldRemoveRequestAndTokensForChildren()
    {
        $user = User::factory()->create();
        // Prepare data
        $parentProcessRequest = ProcessRequest::factory()->create(['status' => 'ACTIVE']);

        $childProcessRequest1 = ProcessRequest::factory()->create([
            'status' => 'ACTIVE',
            'parent_request_id' => $parentProcessRequest->id,
        ]);

        $childProcessRequest2 = ProcessRequest::factory()->create([
            'status' => 'ACTIVE',
            'parent_request_id' => $childProcessRequest1->id,
        ]);

        $parentTokens = ProcessRequestToken::factory()->count(3)->create([
            'process_request_id' => $parentProcessRequest->id,
        ]);

        $childTokens1 = ProcessRequestToken::factory()->count(4)->create([
            'process_request_id' => $childProcessRequest1->id,
        ]);

        $childTokens2 = ProcessRequestToken::factory()->count(5)->create([
            'process_request_id' => $childProcessRequest2->id,
        ]);

        // Assert database has parent and child requests
        $this->assertEquals(1, ProcessRequest::where('id', $parentProcessRequest->id)->count());
        $this->assertEquals(1, ProcessRequest::where('id', $childProcessRequest1->id)->count());
        $this->assertEquals(1, ProcessRequest::where('id', $childProcessRequest2->id)->count());

        // Assert count database has tokens for parent and child requests
        $this->assertEquals(3, ProcessRequestToken::where('process_request_id', $parentProcessRequest->id)->count());
        $this->assertEquals(4, ProcessRequestToken::where('process_request_id', $childProcessRequest1->id)->count());
        $this->assertEquals(5, ProcessRequestToken::where('process_request_id', $childProcessRequest2->id)->count());

        //Remove request
        $url = self::API_TEST_URL . '/' . $childProcessRequest1->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(204);

        // Assert database does not has parent and child requests
        $this->assertEquals(1, ProcessRequest::where('id', $parentProcessRequest->id)->count());
        $this->assertEquals(0, ProcessRequest::where('id', $childProcessRequest1->id)->count());
        $this->assertEquals(0, ProcessRequest::where('id', $childProcessRequest2->id)->count());

        // Assert database does not has parent and child requests tokens
        $this->assertEquals(3, ProcessRequestToken::where('process_request_id', $parentProcessRequest->id)->count());
        $this->assertEquals(0, ProcessRequestToken::where('process_request_id', $childProcessRequest1->id)->count());
        $this->assertEquals(0, ProcessRequestToken::where('process_request_id', $childProcessRequest2->id)->count());
    }

    /**
     * The request does not exist in process
     */
    public function testDeleteProcessRequestNotExist()
    {
        //ProcessRequest not exist
        $url = self::API_TEST_URL . '/' . ProcessRequest::factory()->make()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(405);
    }

    public function testListCanceledProcessRequests()
    {
        ProcessRequest::query()->delete();

        ProcessRequest::factory()->count(5)->create(['status' => 'ACTIVE']);
        ProcessRequest::factory()->count(3)->create(['status' => 'COMPLETED']);
        ProcessRequest::factory()->count(1)->create(['status' => 'CANCELED']);

        // The list of requests should show just ACTIVE requests
        $response = $this->apiCall('GET', self::API_TEST_URL . '?type=in_progress');
        $this->assertEquals(5, $response->json()['meta']['total']);

        // The list of completed does NOT include CANCELED requests
        $response = $this->apiCall('GET', self::API_TEST_URL . '?type=completed');
        $this->assertEquals(3, $response->json()['meta']['total']);

        // The list of all requests includes everything
        $response = $this->apiCall('GET', self::API_TEST_URL . '?type=all');
        $this->assertEquals(9, $response->json()['meta']['total']);
    }

    /**
     * Verifies that a file uploaded in a request can be downloaded
     */
    public function testFileDownload()
    {
        // We create a fake file to upload
        $testFileName = 'test.txt';
        Storage::fake('public');
        $fileUpload = UploadedFile::fake()->create($testFileName, 1);

        // Create a request
        $request = ProcessRequest::factory()->create();

        // Crate a user without administrator privileges
        $user = User::factory()->create([
            'status' => 'ACTIVE',
            'is_administrator' => false,
        ]);

        // Add the file to the request
        $addedMedia = $request
            ->addMedia($fileUpload)
            ->withCustomProperties(['data_name' => 'test'])
            ->toMediaCollection('local');

        $route = self::API_TEST_URL . '/' . $request->id . '/files/' . $addedMedia->id;
        $response = $this->apiCall('GET', $route);

        // Validate the header status code
        $response->assertStatus(200);

        // Verify that a file with the fake file is downloaded
        $this->assertEquals($testFileName, $response->getFile()->getFileName());
    }

    public function testParticipantPermissionsToView()
    {
        $participant = User::factory()->create();
        $otherUser = User::factory()->create();

        $request = ProcessRequest::factory()->create(['status' => 'ACTIVE']);
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'user_id' => $participant->id,
        ]);

        $url = route('api.requests.show', $request);
        $this->user = $otherUser;

        $response = $this->apiCall('get', $url);
        $response->assertStatus(403);

        $this->user = $participant;

        $response = $this->apiCall('get', $url);
        $response->assertStatus(200);

        // Participant can still see the data when completed
        $request->update(['status' => 'COMPLETED']);
        $response = $this->apiCall('get', $url);
        $response->assertStatus(200);
    }

    public function testUserCanEditCompletedData()
    {
        $this->user = User::factory()->create();
        $process = Process::factory()->create();

        $initialProcessRequest = ProcessRequest::factory()->create([
            'status' => 'COMPLETED',
            'data' => ['foo' => 'bar'],
            'process_id' => $process->id,
        ]);

        $url = route('api.requests.update', $initialProcessRequest);

        // Attempt to edit ProcessRequest completed data
        $response = $this->apiCall('put', $url, ['data' => ['foo' => '123']]);

        // Verify we can't (yet)
        $response->assertStatus(403);

        $editAllRequestsData = Permission::where('name', 'edit-request_data')->first();
        $this->user->permissions()->attach($editAllRequestsData);
        $this->user->refresh();
        session()->forget('permissions');
        $response = $this->apiCall('put', $url, ['data' => ['foo' => '123']]);

        // Attempt to edit complete data with the permissions
        // fo the user now set
        $response->assertStatus(204);

        $this->user->permissions()->detach($editAllRequestsData);
        $this->user->refresh();
        session()->forget('permissions');
        $response = $this->apiCall('put', $url, ['data' => ['foo' => '123']]);

        // Try again after removing the permissions
        // from the user
        $response->assertStatus(403);

        // Add process level permission
        $process->usersCanEditData()->sync(
            [$this->user->id => ['method' => 'EDIT_DATA']]
        );

        $process->save();

        // Create a new process request with the update process
        // configuration since the process request now honors
        // the process configuration how it existed when the
        // process request was initiated
        $secondProcessRequest = ProcessRequest::factory()->create([
            'status' => 'COMPLETED',
            'data' => ['foo' => 'bar'],
            'process_id' => $process->id,
        ]);

        $url = route('api.requests.update', $secondProcessRequest);
        $response = $this->apiCall('put', $url, ['data' => ['foo' => '123']]);

        // Verify we can now edit the completed ProcessRequest data
        $response->assertStatus(204);
    }

    /**
     * Test lists of requests and permissions
     *
     * @return void
     */
    public function testGetProcessRequestListAndPermissions()
    {
        // Setup user as non administrator
        $this->user->is_administrator = false;
        $this->user->save();

        ProcessRequest::factory()->count(10)->create([
            'user_id' => $this->user->getKey(),
        ]);

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

        // Create 10 more
        ProcessRequest::factory()->count(10)->create([
            'user_id' => $this->user->getKey(),
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL . '?per_page=15');

        //Validate the header status code
        $response->assertStatus(200);

        // Verify structure
        $response->assertJsonStructure([
            'data' => ['*' => self::STRUCTURE],
            'meta',
        ]);

        // Verify page count
        $this->assertEquals(15, $response->json()['meta']['count']);
        // Verify total count
        $this->assertEquals(20, $response->json()['meta']['total']);

        // Create 10 more for different users
        ProcessRequest::factory()->count(10)->create();

        $response = $this->apiCall('GET', self::API_TEST_URL . '?per_page=15');

        //Validate the header status code
        $response->assertStatus(200);

        // Verify structure
        $response->assertJsonStructure([
            'data' => ['*' => self::STRUCTURE],
            'meta',
        ]);

        // Verify page count
        $this->assertEquals(15, $response->json()['meta']['count']);
        // Verify total count
        $this->assertEquals(20, $response->json()['meta']['total']);
    }

    public function testGetRequestToken()
    {
        $expectedResponse = [
            'advanceStatus',
            'completed_at',
            'completed_at_formatted',
            'completed_by',
            'count',
            'created_at',
            'created_at_formatted',
            'repeat_message',
            'element_id',
            'element_name',
            'is_sequence_flow',
            'status',
            'status_translation',
            'user' => [
                'id',
                'username',
                'firstname',
                'lastname',
                'fullname',
            ],
            'user_id',
        ];

        // Create other User
        $otherUser = User::factory()->create();

        //create a request and a token
        $request = ProcessRequest::factory()->create(['status' => 'ACTIVE']);
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'user_id' => $this->user,
        ]);

        // Validate the status is correct
        $response = $this->apiCall('GET', route('api.requests.getRequestToken', ['request' => $request->id, 'element_id' => $token->element_id]));
        $response->assertStatus(200);

        // Verify structure
        $response->assertJsonStructure($expectedResponse);

        // Validate non existing process element
        $nonExistentElementId = 999;

        $response = $this->apiCall('GET', route('api.requests.getRequestToken', ['request' => $request->id, 'element_id' => $nonExistentElementId]));
        $response->assertStatus(404);

        // Verify with other user without permissions
        $this->user = $otherUser;

        $response = $this->apiCall('GET', route('api.requests.getRequestToken', ['request' => $request->id, 'element_id' => $token->element_id]));
        $response->assertStatus(403);

        $this->user->giveDirectPermission('view-all_requests');
        $this->user->refresh();

        // Verify with other user with permissions
        $response = $this->apiCall('GET', route('api.requests.getRequestToken', ['request' => $request->id, 'element_id' => $token->element_id]));
        $response->assertStatus(200);
    }

    public function testAdvancedFilter()
    {
        $hit = ProcessRequest::factory()->create([
            'data' => ['foo' => 'bar'],
        ]);
        $miss = ProcessRequest::factory()->create([
            'data' => ['foo' => 'baz'],
        ]);

        $filterString = json_encode([
            [
                'subject' => ['type' => 'Field', 'value' => 'data.foo'],
                'operator' => '=',
                'value' => 'bar',
            ],
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL, ['advanced_filter' => $filterString, 'include' => 'data']);
        $json = $response->json();

        $this->assertEquals($hit->id, $json['data'][0]['id']);
    }

    // Test enableIsActionbyemail function
    public function testEnableIsActionbyemail()
    {
        //create a token
        $token = ProcessRequestToken::factory()->create([
            'status' => 'ACTIVE',
        ]);
        $this->assertEquals($token->is_actionbyemail, 0);

        $res = (new ProcessRequestController)->enableIsActionbyemail($token->getKey());

        $this->assertTrue($res);
    }

    /**
     * Test the screenRequested method of ProcessRequestController.
     */
    public function testScreenRequested()
    {
        $request = ProcessRequest::factory()->create();
        ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'element_type' => 'userTask',
            'status' => 'CLOSED',
        ]);

        $params = [
            'page' => 1,
            'per_page' => 10,
            'order_by' => 'completed_at',
            'order_direction' => 'asc',
            'filter' => '',
        ];
        $route = route('api.requests.detail.screen', ['request' => $request->id]);
        $response = $this->apiCall('GET', $route, $params);

        $response->assertStatus(200);
        // Assert empty because tokens does not have screens.
        $data = $response->json()['data'];
        $this->assertEmpty($data);
    }


    /**
     * Get a list of Requests by Cases.
     */
    public function testRequestByCase()
    {
        $case_number = 10;

        ProcessRequest::factory()->count(10)->create([
            'case_number' => $case_number,
        ]);

        $response = $this->apiCall('GET', `requests-by-case?case_number=` . $case_number);

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
}
