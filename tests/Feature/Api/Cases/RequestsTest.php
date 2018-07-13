<?php
namespace Tests\Feature\Api\Cases;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RequestsTest extends ApiTestCase
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
        $response = $this->api('GET', '/api/1.0/requests');
        $response->assertStatus(401);
    }

    /**
     * Test to check that the route returns the correct response    
     */    

    public function test_api_access()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 1)->create([
            'id' => 10,
            'creator_user_id' => \Auth::user()->id
        ]);


        factory(\ProcessMaker\Model\Delegation::class, 2)->create([
            'application_id' => 10
        ]);

        $response = $this->api('GET', '/api/1.0/requests?delay=overdue');

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

    private function login()
    {
        $this->user = factory(User::class)->create([
        'password' => Hash::make('password'),
        'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
    ]);

        $this->auth($this->user->username, 'password');
    }
}
