<?php
namespace Tests\Feature\OAuth2;

use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Api\ApiTestCase;
use ProcessMaker\Model\User;
use Illuminate\Support\Facades\Auth;

class OAuthAuthenticationTest extends ApiTestCase
{
    use DatabaseTransactions;

    /**
     * Test to ensure that calling an api with oauth validation will fail if oauth info is not present
     */
    public function testOAuthFailure()
    {
        Route::get('/_tests/api/test', function() {
            return "passed";
        })->middleware('auth:api');
        $response = $this->json('GET', '/_tests/api/test');
        $response->assertStatus(401);
    }

    /**
     * Test with an invalid token
     */
    public function testOAuthInvalidToken()
    {
        Route::get('/_tests/api/test', function() {
            return "passed";
        })->middleware('auth:api');
        $response = $this->json('GET', '/_tests/api/test', [], [
            'Authorization: Bearer 1234',
        ]);
        $response->assertStatus(401);
    }

    /**
     * Test to ensure we can access a route with auth:api middleware properly with valid credentials
     */
    public function testOAuthValidToken()
    {
        // First, let's fetch a proper access token
        $user = factory(User::class)->create([
            'password' => Hash::make('password')
        ]);

        // Use our ApiTestCase to login and store our access token
        $this->auth($user->username, 'password');

        // Setup our test route, ensuring we use the auth middleware with api guard
        Route::get('/_tests/api/test', function() {
            return Auth::user()->id;
        })->middleware('auth:api');

        // Call using our ApiTestCase api method, which will pass in the required Authentication Bearer header
        $response = $this->api('GET', '/_tests/api/test');

        $response->assertStatus(200);
        // The USR_UID that is in the content is the logged in user, which will be the authenticated user
        $response->assertSeeText($user->id);
    }
}