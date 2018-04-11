<?php
namespace Tests\Feature\Api\Cases;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CasesTest extends ApiTestCase
{
    private $clientId = 'x-pm-local-client';
    private $clientSecret = '179ad45c6ce2cb97cf1029e212046e81';
    private $user;
    use DatabaseTransactions;

    protected function setUp()
    {
        parent::setUp();
    }

    public function test_route_token_missing()
    {
        $this->assertFalse(isset($this->token));
    }

    public function test_api_result_failed()
    {
        $response = $this->api('GET', '/api/1.0/cases');
        $response->assertStatus(401);
    }

    public function test_api_access()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 1)->create();

        $this->assertTrue(isset($this->token));

        $response = $this->api('GET', '/api/1.0/cases');

        $response->assertStatus(200);

        $response->assertJsonStructure([
          'total',
          'per_page',
          'current_page',
          'last_page',
          'first_page_url',
          'last_page_url',
          'next_page_url',
          'prev_page_url',
          'path',
          'from',
          'to',
          'data'
      ]);

        $data = json_decode($response->getContent());
        $this->assertEquals($data->current_page, 1);
        $this->assertTrue(count($data->data) > 0);
        $this->assertNotEquals($data->total, 0);
    }
    public function test_api_paging()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 32)->create();
        $response = $this->api('GET', '/api/1.0/cases/?page=2');

        $response->assertStatus(200);

        $response->assertJsonStructure([
          'total',
          'per_page',
          'current_page',
          'last_page',
          'first_page_url',
          'last_page_url',
          'next_page_url',
          'prev_page_url',
          'path',
          'from',
          'to',
          'data'
      ]);

        $data = json_decode($response->getContent());
        $this->assertEquals($data->current_page, 2);
        $this->assertTrue(count($data->data) > 0);
        $this->assertNotEquals($data->total, 0);
    }

    public function test_api_per_page()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 26)->create();

        $response = $this->api('GET', '/api/1.0/cases/?per_page=25');

        $response->assertStatus(200);

        $response->assertJsonStructure([
          'total',
          'per_page',
          'current_page',
          'last_page',
          'first_page_url',
          'last_page_url',
          'next_page_url',
          'prev_page_url',
          'path',
          'from',
          'to',
          'data'
      ]);

        $data = json_decode($response->getContent());

        $this->assertEquals($data->per_page, 25);
        $this->assertTrue(count($data->data) > 0);
        $this->assertNotEquals($data->total, 0);
    }

    public function test_api_filtering()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 10)->create();

        $response = $this->api('GET', '/api/1.0/cases/?filter=#');

        $response->assertStatus(200);

        $response->assertJsonStructure([
          'total',
          'per_page',
          'current_page',
          'last_page',
          'first_page_url',
          'last_page_url',
          'next_page_url',
          'prev_page_url',
          'path',
          'from',
          'to',
          'data'
      ]);

        $data = json_decode($response->getContent());

        $this->assertTrue(count($data->data) > 0);
        $this->assertNotEquals($data->total, 0);
    }

    public function test_api_sorting()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 10)->create();

        $response = $this->api('GET', '/api/1.0/cases/?sort=APP_TITLE|desc');

        $response->assertStatus(200);

        $response->assertJsonStructure([
          'total',
          'per_page',
          'current_page',
          'last_page',
          'first_page_url',
          'last_page_url',
          'next_page_url',
          'prev_page_url',
          'path',
          'from',
          'to',
          'data'
      ]);

        $data = json_decode($response->getContent());

        $this->assertTrue(count($data->data) > 0);
        $this->assertNotEquals($data->total, 0);
    }

    private function login()
    {
        $this->user = factory(User::class)->create([
        'USR_PASSWORD' => Hash::make('password'),
        'USR_ROLE' => Role::PROCESSMAKER_ADMIN
    ]);

        $this->auth($this->user->USR_USERNAME, 'password');
    }
}
