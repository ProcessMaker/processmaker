<?php
namespace Tests\Feature\Api\Requests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RequestsTest extends TestCase
{
    use DatabaseTransactions;

    const URL_USER_PROCESSES = '/api/1.0/user/processes';

    public $user;

    /**
     * Test to check that the route is protected
     */

    public function testRouteTokenMissing()
    {
        $this->assertFalse(isset($this->token));
    }

    /**
     * Test to check that the route is protected
     */

    public function restApiResultFailed()
    {
        $response = $this->actingAs($this->user, 'api')
                            ->json('GET', '/api/1.0/requests');
        $response->assertStatus(401);
    }

    /**
     * Test to check that the route returns the correct response
     */

    public function testApiAccess()
    {
        $this->login();

        factory(Application::class, 1)->create([
            'id' => 10,
            'creator_user_id' => $this->user->id,
            'APP_STATUS' => Application::STATUS_TO_DO
        ]);


        factory(Delegation::class, 2)->create([
            'application_id' => 10
        ]);

        // We create an instance with status completed
        factory(Application::class, 1)->create([
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

    /*
     * Tests that the sorting and default sorting works.
     */
    public function testUserProcessesListSorting()
    {
        $this->login();

        // Create some categories
        $category1 = factory(ProcessCategory::class)->create([ 'name' => 'A category']);
        $category2 = factory(ProcessCategory::class)->create([ 'name' => 'X category']);

        // Create two processes with different categories and names
        factory(Process::class)->create([
            'name' => 'X name',
            'process_category_id' => $category1->id
        ]);

        factory(Process::class)->create([
            'name' => 'A name',
            'process_category_id' => $category2->id
        ]);

        // We call the process list endpoint with sort conditions
        $response = $this->actingAs($this->user, 'api')
                            ->json('GET', self::URL_USER_PROCESSES . '?order_by=name&order_direction=desc');

        // Assert that the response is correct and the sorting is correct
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('X name', $data['data'][0]['name']);

        // We call the process list endpoint without sort conditions
        $response = $this->actingAs($this->user, 'api')
                            ->json('GET', self::URL_USER_PROCESSES);

        // Assert that the response is correct when no sorting is applied
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('A category', $data['data'][0]['category']);
    }


    private function login()
    {
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password')
        ]);

    }
}
