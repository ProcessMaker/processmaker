<?php

namespace Tests\Feature\Api\Administration;

use Tests\Feature\Api\ApiTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\User;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\EnvironmentVariable;


class EnvironmentVariablesTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_VARIABLES = '/api/1.0/environment-variables';

    public function setUp()
    {
        parent::setUp();
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
    }


    /** @test */
    public function it_should_create_an_environment_variable()
    {
        $data = [
            'name' => 'testvariable',
            'description' => 'test description',
            'value' => 'testsecret'
        ];

        $response = $this->api('POST', self::API_TEST_VARIABLES, $data);

        // Check for created status code
        $response->assertStatus(201);

        // Ensure there is a record in the database that matches

        unset($data['value']);

        $this->assertDatabaseHas('environment_variables', $data);
    }

    /** @test */
    public function it_should_store_values_as_encrypted()
    {
        factory(EnvironmentVariable::class)->create([
            'value' => 'testvalue'
        ]);
        $this->assertDatabaseMissing('environment_variables', ['value' => 'testvalue']);
        // Now fetch record
        $variable = EnvironmentVariable::first();
        $this->assertEquals('testvalue', $variable->value);
    }

    /** @test */
    public function it_should_have_validation_errors_on_name_uniqueness_during_create()
    {
        // Create an environment variable with a set name
        factory(EnvironmentVariable::class)->create([
            'name' => 'testname'
        ]);
        // Data with a duplicate name
        $data = [
            'name' => 'testname',
            'description' => 'test',
            'value' => 'testvalue'
        ];
        $response = $this->api('POST', self::API_TEST_VARIABLES, $data);

        // Check for validation error status code
        $response->assertStatus(422);

        // Ensure the record does NOT exist
        unset($data['value']);
        $this->assertDatabaseMissing('environment_variables', $data);
    }

    /** @test */
    public function it_should_successfully_return_an_environment_variable()
    {
        // Create an environment variable with a set name
        $variable = factory(EnvironmentVariable::class)->create([
            'name' => 'testname',
            'value' => 'testvalue'
        ]);
        $variable->fresh();
        // Is now fetch the variable and see if success
        $response = $this->api('get', self::API_TEST_VARIABLES . '/' . $variable->uid);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'uid',
            'name',
            'description',
        ]);
        // Ensure the JSON response does NOT have value attribute, as this should be hidden
    }


    /** @test */
    public function it_should_have_validation_errors_on_name_uniqueness_during_update()
    {
        // Create an environment variable with a set name for the update
        $variable = factory(EnvironmentVariable::class)->create([
            'name' => 'testname',
            'value' => 'testvalue'
        ]);
        // Create a variable with another name that will clash with the uniqueness rule
        factory(EnvironmentVariable::class)->create([
            'name' => 'anothername',
            'value' => 'testvalue'
        ]);

        $variable->fresh();
        $data = [
            'name' => 'anothername',
            'description' => 'testdescription',
            'value' => 'differentvalue'
        ];
        $response = $this->api('PUT', self::API_TEST_VARIABLES . '/' . $variable->uid, $data);

        // Check for validation error status code
        $response->assertStatus(422);
    }

    /** @test */
    public function it_should_successfully_update_an_environment_variable()
    {
        // Create an environment variable with a set name
        $variable = factory(EnvironmentVariable::class)->create([
            'name' => 'testname',
            'value' => 'testvalue'
        ]);
        $variable->fresh();
        $data = [
            'name' => 'newname',
            'description' => 'newdescription',
            'value' => 'newvalue'
        ];
        $response = $this->api('PUT', self::API_TEST_VARIABLES . '/' . $variable->uid, $data);

        $response->assertStatus(200);

        $data['uid'] = $variable->uid;
        unset($data['value']);
        $this->assertDatabaseHas('environment_variables', $data);

        $variable = EnvironmentVariable::where('name', 'newname')->first();
        $this->assertEquals('newvalue', $variable->value);
    }


    /** @test */
    public function it_should_return_paginated_environment_variables_during_index()
    {
        $this->withoutExceptionHandling();
        factory(EnvironmentVariable::class, 50)->create();
        // Fetch from index
        $response = $this->api('GET', self::API_TEST_VARIABLES);
        // Verify 200 status code
        $response->assertStatus(200);
        // Grab users
        $data = json_decode($response->getContent(), true);
        // Verify we have a total of 9 results (our 5 plus admin plus our created user)
        $this->assertCount(10, $data['data']);
        $this->assertEquals(50, $data['meta']['total']);
    }

    /** @test */
    public function it_should_return_filtered_environment_variables()
    {
        factory(EnvironmentVariable::class, 50)->create();
        // Put in a match
        factory(EnvironmentVariable::class)->create([
            'name' => 'matchingfield'
        ]);
        // Fetch from index
        $response = $this->api('GET', self::API_TEST_VARIABLES . '?filter=' . urlencode('matchingfield'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        // Ensure we have empty results
        $this->assertCount(1, $data['data']);
        $this->assertEquals(1, $data['meta']['total']);
        $this->assertEquals('matchingfield', $data['meta']['filter']);
    }

    /** @test */
    public function it_should_successfully_remove_environment_variable()
    {
        // Create an environment variable with a set name
        $variable = factory(EnvironmentVariable::class)->create([
            'name' => 'testname',
            'value' => 'testvalue'
        ]);
        $variable->fresh();
        $response = $this->api('DELETE', self::API_TEST_VARIABLES . '/' . $variable->uid);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('environment_variables', [
            'name' => 'testname',
            'value' => 'testvalue'
        ]);
    }
}
