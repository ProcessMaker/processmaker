<?php
namespace Tests\Feature\Api\Requests;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\Process;
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
