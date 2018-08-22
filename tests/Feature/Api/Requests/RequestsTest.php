<?php
namespace Tests\Feature\Api\Requests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;
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
            'data',
            'meta'
      ]);
    }

    /**
     * Test to check that the list of requests filters requests by risk, overdue, etc.
     */

    public function testIndex()
    {
        $this->login();
        $userId = $this->user->id;

        factory(Application::class, 1)->create([
            'id' => 10,
            'creator_user_id' => $userId
        ]);

        // Task on time
        factory(Delegation::class)->create([
            'application_id' => 10,
            'uid' => 'onTimeId',
            'task_due_date' => Carbon::now()->addDays(5),
            'risk_date' => Carbon::now()->addDays(3)
        ]);

        // Task on risk
        factory(Delegation::class)->create([
            'application_id' => 10,
            'uid' => 'atRiskId',
            'task_due_date' => Carbon::now()->addDays(2),
            'risk_date' => Carbon::now()->subDays(2)
        ]);

        // Task on risk
        factory(Delegation::class)->create([
            'application_id' => 10,
            'uid' => 'overdueId',
            'task_due_date' => Carbon::now()->subDays(2),
            'risk_date' => Carbon::now()->subDays(3)
        ]);

        // Without filters all the tasks of the request should be returned
        $response = $this->api('GET', '/api/1.0/requests?include=process,delegations,delegations.user');
        $response->assertStatus(200);
        $parsedResponse = json_decode($response->getContent());
        $this->assertCount(3, $parsedResponse->data[0]->delegations);

        // If the delay parameter is = overdue just the overdue delegation should be returned
        $response = $this->api('GET', '/api/1.0/requests?include=process,delegations,delegations.user&delay=overdue');
        $response->assertStatus(200);
        $parsedResponse = json_decode($response->getContent());
        $this->assertCount(1, $parsedResponse->data[0]->delegations);
        $this->assertEquals('overdueId', $parsedResponse->data[0]->delegations[0]->uid);


        // If the delay parameter is = atRisk just the atRisk delegation should be returned
        $response = $this->api('GET', '/api/1.0/requests?include=process,delegations,delegations.user&delay=atRisk');
        $response->assertStatus(200);
        $parsedResponse = json_decode($response->getContent());
        $this->assertCount(1, $parsedResponse->data[0]->delegations);
        $this->assertEquals('atRiskId', $parsedResponse->data[0]->delegations[0]->uid);

        // If the delay parameter is = onTime just the onTime delegation should be returned
        $response = $this->api('GET', '/api/1.0/requests?include=process,delegations,delegations.user&delay=onTime');
        $response->assertStatus(200);
        $parsedResponse = json_decode($response->getContent());
        $this->assertCount(1, $parsedResponse->data[0]->delegations);
        $this->assertEquals('onTimeId', $parsedResponse->data[0]->delegations[0]->uid);
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
