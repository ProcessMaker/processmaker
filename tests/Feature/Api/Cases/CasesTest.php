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
    use DatabaseTransactions;

    /**
     * Test to check that the route is protected     
     */

    public function test_route_token_missing()
    {
        $this->assertFalse(isset($this->token));
    }

    /**
     * Test to check that the route is protected     
     */    

    public function test_api_result_failed()
    {
        $response = $this->api('GET', '/api/1.0/cases');
        $response->assertStatus(401);
    }

    /**
     * Test to check that the route returns the correct response    
     */    

    public function test_api_access()
    {
        $this->login();

        factory(\ProcessMaker\Model\Delegation::class, 51)->create();

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

    }

    /**
     * Test to check that the route returns the correct response when paging    
     */    

    public function test_api_paging()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 75)->create();

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
    }
    
    /**
     * Test to check that the route returns the correct response when the number of 
     * requested records is correct. 
     */    

    public function test_api_per_page()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 26)->create();

        $response = $this->api('GET', '/api/1.0/cases/?limit=21');

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

        $this->assertEquals($data->per_page, 21);

    }

    /**
     * Test to check that the route returns the correct response when adding a filter
     */        
    
    public function test_api_filtering()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 10)->create();

        $response = $this->api('GET', '/api/1.0/cases/?columnSearch=APP_TITLE&search=Test');

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

        $this->assertTrue(is_array($data->data));
        
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
