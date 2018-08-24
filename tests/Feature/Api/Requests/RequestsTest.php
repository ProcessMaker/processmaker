<?php
namespace Tests\Feature\Api\Requests;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RequestsTest extends TestCase
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

        factory(\ProcessMaker\Model\Application::class, 1)->create([
            'id' => 10,
            'creator_user_id' => $this->user->id,
            'APP_STATUS' => Application::STATUS_TO_DO
        ]);


        factory(\ProcessMaker\Model\Delegation::class, 2)->create([
            'application_id' => 10
        ]);

        // We create an instance with status completed
        factory(\ProcessMaker\Model\Application::class, 1)->create([
            'creator_user_id' => $this->user->id,
            'APP_STATUS' => Application::STATUS_COMPLETED
        ]);

        $response = $this->actingAs($this->user, 'api')->json('GET', '/api/1.0/requests?delay=overdue');
        $parsedResponse = json_decode($response->getContent());
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta'
        ]);
        $this->assertCount(1, $parsedResponse->data,
            'The number of results should be 1, because just one application has status TO_DO ');
    }

    private function login()
    {
        $this->user = factory(User::class)->create([
        'password' => Hash::make('password'),
        'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
    ]);

    }
}
