<?php

namespace Tests\Feature\Api\Cases;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessVariable;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class ProcessVariableControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    /**
     * Tests that all variables of a process are returned
     */
    public function testGetAllProcessVariables()
    {
        $variable = factory(ProcessVariable::class)->create();

        $url = "/api/1.0/project/".$variable->process->uid."/process-variables";
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $returnedList = json_decode($response->getContent());
        $this->assertGreaterThanOrEqual(1, count($returnedList), 'At least one additional variable exists should exist');
    }

    /**
     * Tests the endpoint that gets one variable
     */
    public function testGetOneVariable()
    {
        // we create a process
        $process = factory(Process::class)->create();

        // we create a db source for the variable
        $dbSource = factory(DbSource::class)->create(['process_id' => $process->id]);

        $acceptedValues = '[{"id" : 1, "name" : "niño"}, {"id" : 2, "name":"堂吉诃德是"}]';

        $variable = factory(ProcessVariable::class)
                    ->create([
                        'PRO_ID' => $process->id,
                        'VAR_DBCONNECTION' => $dbSource->uid,
                        'VAR_ACCEPTED_VALUES' => $acceptedValues
                    ]);

        // we create a process that won't have the variable
        $randomProcess = factory(Process::class)->create();

        // test the validation that the process must own the variable
        $url = "/api/1.0/project/" . $randomProcess->uid ."/process-variables/".$variable->VAR_UID;
        $response = $this->api('GET', $url);
        $response->assertStatus(422);

        // we retrieve the process variable using the endpoint
        $url = "/api/1.0/project/".$variable->process->uid."/process-variables/".$variable->VAR_UID;
        $response = $this->api('GET', $url);
        $response->assertStatus(200);
        $returnedModel = json_decode($response->getContent());
        $this->assertTrue($returnedModel->VAR_UID == $variable->VAR_UID);
        $this->assertContains('niño', $returnedModel->VAR_ACCEPTED_VALUES);
        $this->assertContains('堂吉诃德是', $returnedModel->VAR_ACCEPTED_VALUES);
        $this->assertContains($dbSource->server, $returnedModel->VAR_DBCONNECTION_LABEL);
    }

    /**
     * Tests the creation of a process variable
     */
    public function testCreateOneVariable()
    {
        $newVarData = [
            'var_name' => 'Country',
            'var_field_type' => 'wrongType',
            'var_field_size' => 0,
            'var_label' => 'string',
            'var_sql' => '',
            'var_default' => 'MX',
            'inp_doc_uid' => '',
            'var_accepted_values' => '[{"id" : 1, "name" : "niño"}, {"id" : 2, "name" : "堂吉诃德是"}]'
        ];

        // a wrong field must return an validation error
        $process = factory(Process::class)->create();
        $url = "/api/1.0/project/".$process->uid."/process-variables";
        $response = $this->api('POST', $url, $newVarData);
        $response->assertStatus(422);

        $newVarData['var_field_type'] = 'string';
        $response = $this->api('POST', $url, $newVarData);
        $response->assertStatus(201);
        $returnedModel = json_decode($response->getContent());
        $this->assertEquals($returnedModel->VAR_NAME, $newVarData['var_name']);
        $this->assertEquals($returnedModel->PRO_ID, $process->id, 'The variable should be owned by the process');

        // a duplicated variable (with the same name) should return an error should return an error
        $response = $this->api('POST', $url, $newVarData);
        $response->assertStatus(400);
    }

    /**
     * Tests the update of a process variable
     */
    public function testUpdateOneVariable()
    {
        // we create a variable
        $variable = factory(ProcessVariable::class)->create();

        // we create a process that won't have the variable
        $process = factory(Process::class)->create();

        // data to update the variable
        $newVarData = [
            'var_name' => 'changed_name',
            'var_field_type' => 'multiplefile'
        ];

        //test the validation that the process must own the variable
        $url = "/api/1.0/project/" . $process->uid ."/process-variables/".$variable->VAR_UID;
        $response = $this->api('PUT', $url, $newVarData);
        $response->assertStatus(422);

        $url = "/api/1.0/project/".$variable->process->uid."/process-variables/".$variable->VAR_UID;
        $response = $this->api('PUT', $url, $newVarData);
        $response->assertStatus(200);
        $returnedModel = json_decode($response->getContent());
        $this->assertEquals($returnedModel->VAR_NAME, $newVarData['var_name'],
            'The variable name should have been updated');
    }

    /**
     * Tests the update of a process variable
     */
    public function testDeleteOneVariable()
    {
        // we create a variable
        $variable = factory(ProcessVariable::class)->create();

        // we create a process that won't have the variable
        $process = factory(Process::class)->create();

        //test the validation that the process must own the variable
        $url = "/api/1.0/project/" . $process->uid ."/process-variables/".$variable->VAR_UID;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(422);

        $countBefore = ProcessVariable::count();
        $url = "/api/1.0/project/".$variable->process->uid."/process-variables/".$variable->VAR_UID;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(204);
        $countAfter = ProcessVariable::count();
        $this->assertEquals($countAfter + 1, $countBefore, 'The should be on less variable after the deletion');
    }

    /**
     * Overwrite of the setup method that authenticates and fills the default connection data
     */
    protected function setUp()
    {
        parent::setUp();

        // we need an user and authenticate him
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        $this->auth($user->username, 'password');
    }

}