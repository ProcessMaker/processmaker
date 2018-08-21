<?php
namespace Tests\Feature\Api\OAuth2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\User;
use Tests\TestCase;

class OAuth2GrantTest extends TestCase
{
    use DatabaseTransactions;

    private $clientId = 'x-pm-local-client';
    private $clientSecret = '179ad45c6ce2cb97cf1029e212046e81';

    /**
     * Test to determine if credentials for an invalid user properly is rejected
     */
    public function testUnsuccessfulPasswordGrant()
    {
         $response = $this->json('POST','/oauth2/token', [
            'grant_type' => 'password',
            'scope' => '*',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => 'invaliduser',
            'password' => 'password'
         ]);
         // Make sure we're receiving an Unauthorized
        $response->assertStatus(401);
    }

    /**
     * Test to ensure we support the password grant
     *
     * @return void
     */
    public function testSuccessfulPasswordGrant()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password')
        ]);
        $response = $this->json('POST','/oauth2/token', [
            'grant_type' => 'password',
            'scope' => '*',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => $user->username,
            'password' => 'password'
        ]);
        $response->assertStatus(200);
        // Make sure we have a valid OAuth2 response with token data
        $response->assertJsonStructure([
            'token_type',
            'expires_in',
            'access_token',
            'refresh_token'
        ]);
    }

    /**
     * Determines if successful client credentials grant works properly
     */
    public function testSuccessfulClientCredentialsGrant()
    {
         $user = factory(User::class)->create([
            'password' => Hash::make('password')
        ]);
        $response = $this->json('POST','/oauth2/token', [
            'grant_type' => 'client_credentials',
            'scope' => '*',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
        $response->assertStatus(200);
        // Make sure we have a valid OAuth2 response with token data
        $response->assertJsonStructure([
            'token_type',
            'expires_in',
            'access_token'
        ]);
    }

    /**
     * This tests authorization code grant type. Note this requires user authorization which is done
     * through checking for the authorize frontend returning and then manually submitting an approval of
     * authorization.
     */
    public function testSuccessfulAuthorizationCodeGrant()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password')
        ]);
        $response = $this->call('GET', '/oauth2/authorize', [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'scope' => '*'
        ]);
        // Make sure we get a proper part one response for Auth Code Grant Flow
        $response->assertRedirect('/en/base/login/login?u=%2Foauth2%2Fauthorize%3Fresponse_type%3Dcode%26client_id%3D' . $this->clientId . '%26scope%3D%252A');
        // Now, this would ensure the user logs in (which we'll manually set)
        // If we do that, then it should redirect us to the oauth2 authorize screen
        Auth::login($user);
        $response = $this->call('GET', '/oauth2/authorize', [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'scope' => '*'
        ]);
        $response->assertViewIs('oauth2.authorize');
        // Okay, let's authorize
        $response = $this->call('POST', '/oauth2/authorize?' . http_build_query([
                'response_type' => 'code',
                'client_id' => $this->clientId,
                'scope' => '*'
            ]), [
            'approve' => 'Accept'
        ]);
        // We should now receive a redirect to the oauth2 client redirect_uri with a valid code parameter
        $response->assertStatus(302);
        $redirectUri = $response->headers->get('location');
        $parts = parse_url($redirectUri);
        $this->assertArrayHasKey('query', $parts);
        parse_str($parts['query'], $data);
        $this->assertArrayHasKey('code', $data);
        // Now, let's call our url again, but this time with code
        $response = $this->json('POST','/oauth2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => request()->getSchemeAndHttpHost() . '/oauth2/grant',
            'code' => urldecode($data['code'])
        ]);
        $response->assertJsonStructure([
            'token_type',
            'expires_in',
            'access_token',
            'refresh_token'
        ]);
    }

    /**
     * Test OAuth2 Implicit Grant type which is similar to auth code grant
     */
    public function testSuccessfulImplicitGrant()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password')
        ]);
        $response = $this->call('GET', '/oauth2/authorize', [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'scope' => '*'
        ]);
        // Make sure we get a proper part one response for Auth Code Grant Flow
        $response->assertRedirect('/en/base/login/login?u=%2Foauth2%2Fauthorize%3Fresponse_type%3Dcode%26client_id%3D' . $this->clientId . '%26scope%3D%252A');
        // Now, this would ensure the user logs in (which we'll manually set)
        // If we do that, then it should redirect us to the oauth2 authorize screen
        Auth::login($user);
        $response = $this->call('GET', '/oauth2/authorize', [
            'response_type' => 'token',
            'client_id' => $this->clientId,
            'scope' => '*'
        ]);
        $response->assertViewIs('oauth2.authorize');
        // Okay, let's authorize
        $response = $this->call('POST', '/oauth2/authorize?' . http_build_query([
                'response_type' => 'token',
                'client_id' => $this->clientId,
                'scope' => '*'
            ]), [
            'approve' => 'Accept'
        ]);
        // We should now receive a redirect to our redirect uri for our client along with
        // Our token
        $response->assertStatus(302);
        $redirectUri = $response->headers->get('location');
        $parts = parse_url($redirectUri);
        $this->assertArrayHasKey('fragment', $parts);
        parse_str($parts['fragment'], $data);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('token_type', $data);
        $this->assertArrayHasKey('expires_in', $data);
        $this->assertEquals('Bearer', $data['token_type']);
    }

    /**
     * Test our OAuth2 refresh token request process
     */
    public function testSuccessfulRefreshTokenRequest()
    {
        // First, let's get a valid oauth token
        $user = factory(User::class)->create([
            'password' => Hash::make('password')
        ]);
        $response = $this->json('POST','/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => $user->username,
            'password' => 'password'
        ]);
        $tokenData = json_decode($response->getContent(), true);
        $response = $this->json('POST','/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $tokenData['refresh_token']
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token_type',
            'expires_in',
            'access_token',
            'refresh_token'
        ]);

    }

}
