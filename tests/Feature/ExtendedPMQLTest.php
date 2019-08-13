<?php

namespace Tests\Feature;

use DB;
use Faker\Factory;
use Tests\TestCase;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\ProcessRequestToken;

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
        $query = 'created = "' . $date->format('Y-m-d H:i:s') . '"';
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
}
