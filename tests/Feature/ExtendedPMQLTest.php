<?php

namespace Tests\Feature;

use Carbon\Carbon;
use DB;
use Faker\Factory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ExtendedPMQLTest extends TestCase
{
    use RequestHelper;

    public function testHandleFieldAlias()
    {
        // Instantiate Faker
        $faker = Factory::create();

        // Set our fake date
        $date = $faker->dateTime();

        // Create a process request with our fake created_at date
        $processRequest = factory(ProcessRequest::class)->create([
            'created_at' => $date,
        ]);

        // Construct & run a PMQL query using the "created" field alias
        $query = 'created = "'.$date->format('Y-m-d H:i:s').'"';
        $pmqlResult = ProcessRequest::pmql($query)->first();

        // Assert that the models match
        $this->assertTrue($processRequest->is($pmqlResult));
    }

    public function testHandleValueAlias()
    {
        // Create a process request
        $processRequest = factory(ProcessRequest::class)->create([
            'status' => 'ACTIVE',
        ]);

        // Construct & run a PMQL query using the "status" value alias
        $query = 'status = "In Progress"';
        $pmqlResult = ProcessRequest::pmql($query)->get();

        // Assert that the model is returned in our search
        $ids = $pmqlResult->pluck('id');
        $this->assertContains($processRequest->id, $ids);
    }

    public function testHandleFieldWildcard()
    {
        $this->markTestSkipped('PMQL does not yet support JSON fields on Microsoft SQL Server.');

        // Instantiate Faker
        $faker = Factory::create();

        //Generate fake data
        $data = [
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
        ];

        // Create a process request
        $processRequest = factory(ProcessRequest::class)->create([
            'data' => $data,
        ]);

        // Create a process request token tied to the above request
        $processRequestToken = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $processRequest->id,
        ]);

        // Construct & run a PMQL query using the "data" field wildcard
        $query = "data.first_name = \"{$data['first_name']}\" AND data.last_name = \"{$data['last_name']}\"";
        $pmqlResult = ProcessRequestToken::pmql($query)->first();

        // Assert that the models match
        $this->assertTrue($processRequestToken->is($pmqlResult));
    }

    public function testInUsersTimezone()
    {
        // Ensure the mysql server timezone is set to UTC
        $this->assertContains(\DB::select('select @@time_zone as tz')[0]->tz, ['+00:00', 'UTC']);

        // Ensure the app timezone is set to UTC
        config(['app.timezone' => 'UTC']);

        $this->user->timezone = 'America/Los_Angeles';
        $this->user->save();

        $processRequest1 = factory(ProcessRequest::class)->create([
            'completed_at' => '2021-10-05 16:00:00', // UTC
        ]);
        $processRequest2 = factory(ProcessRequest::class)->create([
            'completed_at' => '2021-10-05 18:00:00', // UTC
        ]);

        $url = route('api.requests.index', ['pmql' => 'completed_at > "2021-10-05 10:00:00"']); // America/Los_Angeles
        $result = $this->apiCall('GET', $url);
        $this->assertCount(1, $result->json()['data']); // Match only the one created at 11am Los Angeles Time (18:00/6pm UTC)
        $this->assertEquals($processRequest2->id, $result->json()['data'][0]['id']);
    }

    public function testRelativeDate()
    {
        $processRequest1 = factory(ProcessRequest::class)->create([
            'data' => ['date' => Carbon::parse('-10 minutes')->toDateTimeString()],
        ]);
        $processRequest2 = factory(ProcessRequest::class)->create([
            'data' => ['date' => Carbon::parse('-2 hours')->toDateTimeString()],
        ]);

        $url = route('api.requests.index', ['pmql' => 'data.date > now -1 hour']);
        $result = $this->apiCall('GET', $url);
        $this->assertCount(1, $result->json()['data']); // Match only the one that completed 10 minutes ago
        $this->assertEquals($processRequest1->id, $result->json()['data'][0]['id']);
    }

    public function testCharComparison()
    {
        $processRequest1 = factory(ProcessRequest::class)->create([
            'data' => ['gender' => 'F'],
        ]);

        $processRequest2 = factory(ProcessRequest::class)->create([
            'data' => ['gender' => 'M'],
        ]);

        $url = route('api.requests.index', ['pmql' => 'data.gender = "F"']);
        $result = $this->apiCall('GET', $url);
        $this->assertCount(1, $result->json()['data']); // Match only F
        $this->assertEquals($processRequest1->id, $result->json()['data'][0]['id']);
    }

    public function testFilterUsernameWithNumbers()
    {
        $user = factory(User::class)->create([
            'username' => 'W0584',
        ]);
        $processRequest = factory(ProcessRequest::class)->create([
            'user_id' => $user->id,
        ]);

        $url = route('api.requests.index', ['pmql' => 'requester = "W0584"']);
        $result = $this->apiCall('GET', $url);
        $requesterId = $result->json()['data'][0]['user_id'];
        $this->assertEquals($requesterId, $user->id);
    }
}
