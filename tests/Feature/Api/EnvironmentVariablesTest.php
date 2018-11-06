<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;
use ProcessMaker\Models\EnvironmentVariable;
use Tests\Feature\Shared\RequestHelper;

class EnvironmentVariablesTest extends TestCase
{
    use RequestHelper;

    const API_TEST_VARIABLES = '/environment_variables';

    /** @test */
    public function it_should_create_an_environment_variable()
    {
        $data = [
            'name' => 'testvariable',
            'description' => 'test description',
            'value' => 'testsecret'
        ];

        $response = $this->apiCall('POST', self::API_TEST_VARIABLES, $data);

        // Check for created status code
        $response->assertStatus(201);

        // Ensure there is a record in the database that matches

        unset($data['value']);

        $this->assertDatabaseHas('environment_variables', $data);
    }

    /** @test */
    public function it_should_store_values_as_encrypted()
    {
        $variable = factory(EnvironmentVariable::class)->create([
            'value' => 'testvalue'
        ]);
        $this->assertDatabaseMissing('environment_variables', ['value' => 'testvalue']);
        // Now fetch record
        $variable->refresh();
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
        $response = $this->apiCall('POST', self::API_TEST_VARIABLES, $data);

        // Check for validation error status code
        $response->assertStatus(422);

        // Ensure the record does NOT exist
        unset($data['value']);
        $this->assertDatabaseMissing('environment_variables', $data);
    }

    /** @test */
    public function it_should_not_allow_whitespace_in_variable_name()
    {
        // Data with a name with a space
        $data = [
            'name' => 'test name',
            'description' => 'test',
            'value' => 'testvalue'
        ];
        $response = $this->apiCall('POST', self::API_TEST_VARIABLES, $data);

        // Check for validation error status code
        $response->assertStatus(422);
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
        $response = $this->apiCall('get', self::API_TEST_VARIABLES . '/' . $variable->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'id',
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
        $response = $this->apiCall('PUT', self::API_TEST_VARIABLES . '/' . $variable->id, $data);

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
        $response = $this->apiCall('PUT', self::API_TEST_VARIABLES . '/' . $variable->id, $data);

        $response->assertStatus(200);

        $data['id'] = $variable->id;
        unset($data['value']);
        $this->assertDatabaseHas('environment_variables', $data);

        $variable = EnvironmentVariable::where('name', 'newname')->first();
        $this->assertEquals('newvalue', $variable->value);
    }


    /** @test */
    public function it_should_return_paginated_environment_variables_during_index()
    {
        // Can't truncate because of DatabaseTransactions
        EnvironmentVariable::whereNotNull('id')->delete();

        $this->withoutExceptionHandling();
        factory(EnvironmentVariable::class, 50)->create();
        // Fetch from index
        $response = $this->apiCall('GET', self::API_TEST_VARIABLES);
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
        $response = $this->apiCall('GET', self::API_TEST_VARIABLES . '?filter=' . urlencode('matchingfield'));
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
        $response = $this->apiCall('DELETE', self::API_TEST_VARIABLES . '/' . $variable->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('environment_variables', [
            'name' => 'testname',
            'value' => 'testvalue'
        ]);
    }

    /** @test */
    public function it_value_does_not_change_if_value_is_null()
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
            'value' => ''
        ];
        $response = $this->apiCall('PUT', self::API_TEST_VARIABLES . '/' . $variable->id, $data);

        $response->assertStatus(200);

        $data['id'] = $variable->id;
        unset($data['value']);
        $this->assertDatabaseHas('environment_variables', $data);

        $variable = EnvironmentVariable::where('name', 'newname')->first();
        $this->assertEquals('testvalue', $variable->value);
    }
}
