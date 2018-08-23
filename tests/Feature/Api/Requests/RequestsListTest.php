<?php
namespace Tests\Feature\Api\Cases;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RequestsListTest extends TestCase
{
    use DatabaseTransactions;

    public $user;

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
        $response = $this->json('GET', '/api/1.0/requests');
        $response->assertStatus(401);
    }

    /**
     * Test to check that the route returns the correct response    
     */    

    public function test_api_access()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 51)->create([
            'creator_user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user, 'api')->json('GET', '/api/1.0/requests');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data',
            'meta' => [
                'count',
                'current_page',
                'filter',
                'per_page',
                'sort_by',
                'sort_order',
                'total',
                'total_pages'
            ],
        ]);
      
        $data = json_decode($response->getContent());
        $this->assertEquals($data->meta->current_page, 1);
        $this->assertTrue(count($data->data) > 0);

    }

    /**
     * Test to check that the route returns the correct response when paging    
     */    

    public function test_api_paging()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 75)->create();

        $response = $this->actingAs($this->user, 'api')->json('GET', '/api/1.0/requests/?page=2');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data',
            'meta' => [
                'count',
                'current_page',
                'filter',
                'per_page',
                'sort_by',
                'sort_order',
                'total',
                'total_pages'
            ],
      ]);

        $data = json_decode($response->getContent());

        $this->assertEquals($data->meta->current_page, 2);
    }
    
    /**
     * Test to check that the route returns the correct response when the number of 
     * requested records is correct. 
     */    

    public function test_api_per_page()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 26)->create();

        $response = $this->actingAs($this->user, 'api')->json('GET', '/api/1.0/requests/?per_page=21');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data',
            'meta' => [
                'count',
                'current_page',
                'filter',
                'per_page',
                'sort_by',
                'sort_order',
                'total',
                'total_pages'
            ],
      ]);

        $data = json_decode($response->getContent());

        $this->assertEquals(21, $data->meta->per_page);

    }

    /**
     * Test to check that the route returns the correct response when adding a filter
     */
    public function test_api_filtering()
    {
        $this->login();

        factory(\ProcessMaker\Model\Application::class, 10)->create();

        $response = $this->actingAs($this->user, 'api')->json('GET', '/api/1.0/requests/?columnSearch=APP_TITLE&search=Test');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data',
            'meta' => [
                'count',
                'current_page',
                'filter',
                'per_page',
                'sort_by',
                'sort_order',
                'total',
                'total_pages'
            ],
      ]);

        $data = json_decode($response->getContent());

        $this->assertTrue(is_array($data->data));
        
    }
    
    private function login()
    {
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

    }
}
