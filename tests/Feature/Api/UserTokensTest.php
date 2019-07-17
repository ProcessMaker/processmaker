<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class UserTokensTest extends TestCase
{

    use RequestHelper;

    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }

    /**
     * Get a list of User tokens without query parameters.
     */
    public function testListTokensWithEmptyTokens()
    {
        $user = $this->user;
        $response = $this->apiCall('GET', "/users/" . $user->id . "/tokens");

        //Validate the header status code
        $response->assertStatus(200);

        // Verify count
        $this->assertEquals(0, $response->json()['meta']['total']);
    }

    /**
     * Get a list of User tokens without query parameters for another user.
     */
    public function testListTokensWithEmptyTokensForOtherUser()
    {
        $user = factory(User::class)->create();
        $response = $this->apiCall('GET', "/users/" . $user->id . "/tokens");

        //Validate the header status code
        $response->assertStatus(200);

        // Verify count
        $this->assertEquals(0, $response->json()['meta']['total']);
    }

    public function testPermissionDeniedForUserWithoutViewPermissions()
    {
        $this->debug = false;
        $user = factory(User::class)->create();
        $this->user = $user;
        
        $targetUser = factory(User::class)->create();

        $response = $this->apiCall('GET', "/users/" . $targetUser->id . "/tokens");
        $response->assertStatus(403);

    }


    /**
     * Test validation failure
     */
    public function testTokenCreateValidationError()
    {
        $user = $this->user;
        $response = $this->apiCall('POST', "/users/" . $user->id . "/tokens");

        //Validate the header status code
        $response->assertStatus(422);
    }

    public function testPermissionDeniedForUserWithoutEditPermissionsForCreatingToken()
    {
        $this->debug = false;
        $user = factory(User::class)->create();
        $this->user = $user;
        
        $targetUser = factory(User::class)->create();

        $response = $this->apiCall('POST', "/users/" . $targetUser->id . "/tokens");
        $response->assertStatus(403);

    }



    /**
     * Test creation of a user token with a default expire of 1 year
     */
    public function testCreateTokenDefaultExpire()
    {
        $now =  new Carbon();
        $user = $this->user;
        $response = $this->apiCall('POST', "/users/" . $user->id . "/tokens", [
            'name' => 'Test Token'
        ]);

        //Validate the header status code
        $response->assertStatus(200);

        $responseObj = $response->decodeResponseJson();

        // Verify if the token expires_at is 1 year into the future, just check the dates, not seconds
        $checkDate = $now->addYear()->startOfDay();

        $expireDate = (new Carbon($responseObj['token']['expires_at']))->startOfDay();

        $this->assertTrue($checkDate->equalTo($expireDate));
    }

    public function testListingWithExistingToken()
    {
        $user = $this->user;
        $response = $this->apiCall('POST', "/users/" . $user->id . "/tokens", [
            'name' => 'Test Token'
        ]);

        //Validate the header status code
        $response->assertStatus(200);

        $response = $this->apiCall('GET', "/users/" . $user->id . "/tokens");

        //Validate the header status code
        $response->assertStatus(200);

        // Verify count
        $this->assertEquals(1, $response->json()['meta']['total']);
    }

    public function testShowToken()
    {
        $user = $this->user;
        $response = $this->apiCall('POST', "/users/" . $user->id . "/tokens", [
            'name' => 'Test Token'
        ]);

        //Validate the header status code
        $response->assertStatus(200);

        $responseObj = $response->decodeResponseJson();
        $response = $this->apiCall('GET', "/users/" . $user->id . "/tokens/" . $responseObj['token']['id']);

        //Validate the header status code
        $response->assertStatus(200);
    }

    public function testPermissionDeniedForUserWithoutViewPermissionsForViewingToken()
    {
        $this->debug = false;

        $targetUser = factory(User::class)->create();


        $user = $this->user;
        $response = $this->apiCall('POST', "/users/" . $user->id . "/tokens", [
            'name' => 'Test Token'
        ]);

        //Validate the header status code
        $response->assertStatus(200);

        $responseObj = $response->decodeResponseJson();

        $targetUser = $user;
        $user = factory(User::class)->create();
        $this->user = $user;

        $response = $this->apiCall('GET', "/users/" . $targetUser->id . "/tokens/" . $responseObj['token']['id']);

        //Validate the header status code
        $response->assertStatus(403);
    }

    public function testRevokeTokenForUser()
    {
        $user = $this->user;
        $response = $this->apiCall('POST', "/users/" . $user->id . "/tokens", [
            'name' => 'Test Token'
        ]);

        //Validate the header status code
        $response->assertStatus(200);

        $responseObj = $response->decodeResponseJson();

        $response = $this->apiCall('DELETE', "/users/" . $user->id . "/tokens/" . $responseObj['token']['id']);

        //Validate the header status code
        $response->assertStatus(204);

        // Verify that listing is 0
        $response = $this->apiCall('GET', "/users/" . $user->id . "/tokens");

        //Validate the header status code
        $response->assertStatus(200);

        // Verify count
        $this->assertEquals(0, $response->json()['meta']['total']);
    }

    public function testPermissionDeniedForUserWithoutEditPermissionsForDeletingToken()
    {
        $this->debug = false;

        $targetUser = factory(User::class)->create();


        $user = $this->user;
        $response = $this->apiCall('POST', "/users/" . $user->id . "/tokens", [
            'name' => 'Test Token'
        ]);

        //Validate the header status code
        $response->assertStatus(200);

        $responseObj = $response->decodeResponseJson();

        $targetUser = $user;
        $user = factory(User::class)->create();
        $this->user = $user;

        $response = $this->apiCall('DELETE', "/users/" . $targetUser->id . "/tokens/" . $responseObj['token']['id']);

        //Validate the header status code
        $response->assertStatus(403);
    }

   

    public function test404WithRevokeOfUnknownToken()
    {
        $user = $this->user;
        $response = $this->apiCall('DELETE', "/users/" . $user->id . "/tokens/12345");
        //Validate the header status code
        $response->assertStatus(404);

    }
}
