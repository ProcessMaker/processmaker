<?php
namespace Tests\Feature\Api;

use Tests\TestCase;

class ApiTestCase extends TestCase
{
    private $clientId = 'x-pm-local-client';
    private $clientSecret = '179ad45c6ce2cb97cf1029e212046e81';

    // Our current access token
    protected $token;

    //Route API_ROUTE
    public const API_ROUTE = '/api/1.0/';

    // Performs an OAuth2 Password grant
    protected function auth($username, $password)
    {
        $response = $this->json('POST','/oauth2/token', [
            'grant_type' => 'password',
            'scope' => '*',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => $username,
            'password' => $password
        ]);
        $data = json_decode($response->getContent(), true);
        $this->token = $data['access_token'];
    }

    /**
     * Call the given api URI with a JSON request.
     * Automatically adds an Authorization Bearer header
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function api($method, $uri, array $data = [], array $headers = [])
    {
        $headers['Authorization'] = 'Bearer ' . $this->token;

        return $this->json($method, $uri, $data, $headers);
    }
}