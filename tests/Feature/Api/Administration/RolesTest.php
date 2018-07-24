<?php

namespace Tests\Feature\Api\Administration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use ProcessMaker\Transformers\RoleTransformer;
use Tests\Feature\Api\ApiTestCase;

/**
 * Tests the Roles API Endpoints with expected values
 */
class RolesTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_ROLES = '/api/1.0/roles';

    /**
     * 
     * These api endpoints can only work if you are authenticated
     */
    public function testUnauthenticated()
    {
        $response = $this->api('GET', self::API_TEST_ROLES);
        $response->assertStatus(401);
    }

    /**
     * Ensure our API endpoint is protected by required permission
     */
    public function testUnauthorized()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => null,
        ]);
        // No role means it should not be authorized
        $this->auth($user->username, 'password');
        $response = $this->api('GET', self::API_TEST_ROLES);
        $response->assertStatus(403);
    }
    /**
     * Verify roles listing is working with no filters provided
     */
    public function testRolesListing()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        // Build a sample of 5 roles into the system
        $roles = factory(Role::class, 5)->create();
        // Fetch via API
        $response = $this->api('GET', self::API_TEST_ROLES);
        // Verify 200 status code
        $response->assertStatus(200);
        // Grab users
        $data = json_decode($response->getContent(), true);
        // Verify we have a total of 8 results (our 5 plus our 3 from our seeders)
        $this->assertCount(8, $data['data']);
        $this->assertEquals(8, $data['meta']['total']);
        // Not testing returned data format as we're assuming the single role fetch validates that 
        // output matches transformer
    }

    /**
     * Test to ensure our filters are working and not matching on invalid filter criteria
     */
    public function testRoleSearchNoMatches()
    {
       $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('GET', self::API_TEST_ROLES . '?filter=' . urlencode('invalid'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(),true);
        // Ensure we have empty results
        $this->assertCount(0, $data['data']);
        $this->assertEquals(0, $data['meta']['total']);
        $this->assertEquals('invalid', $data['meta']['filter']);
    }

    /**
     * Test to ensure our filter is working for user search
     */
    public function testFilterMatch()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        // Now create a role that would match
        $role = factory(Role::class)->create([
            'name' => 'Test Matching Role'
        ]);
        $response = $this->api('GET', self::API_TEST_ROLES . '?filter=' . urlencode('Matching Ro'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(),true);
        // Ensure we have empty results
        $this->assertCount(1, $data['data']);
        $this->assertEquals(1, $data['meta']['total']);
        $this->assertEquals('Matching Ro', $data['meta']['filter']);
        // We refetch from the DB to ensure we have all columns from DB when transforming
        $transformed = (new RoleTransformer())->transform(Role::find($role->id));
        $this->assertEquals($transformed, $data['data'][0]);
    }

    /**
     * Test to ensure we receive a 404 when passing in a uid not found
     */
    public function testRoleNotFound()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('get', self::API_TEST_ROLES . '/invaliduid');
        $response->assertStatus(404);
    }

    /**
     * Test fetch single role fetch from api
     */
    public function testRoleGet()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $role = factory(Role::class)->create();
        $response = $this->api('get', self::API_TEST_ROLES . '/' . $role->uid->toString());
        $response->assertStatus(200);
        // Get our expected transformed role
        $expected = (new RoleTransformer())->transform($role);
        // Now verify that's what we got
        $response->assertJson($expected);
    }

    /**
     * Test fetch single role fetch from api with one user assigned to role
     */
    public function testRoleGetWithUserAssigned()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $role = factory(Role::class)->create();
        $newUser = factory(USer::class)->create([
            'role_id' => $role->id
        ]);
        $response = $this->api('get', self::API_TEST_ROLES . '/' . $role->uid->toString());
        $response->assertStatus(200);
       // Now ensure that we have 1 user assigned
        $response->assertJson(['total_users' => 1]);
    }

    public function testRoleCreateWithValidationFailure()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('post', self::API_TEST_ROLES, [
            
        ]);
        // Empty, we should receive validation errors
        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'name' => ['The name field is required.'],
                'code' => ['The code field is required.'],
                'status' => ['The status field is required.']
            ]
        ]);
        // Ensure out of bounds checks on validation
        $response = $this->api('post', self::API_TEST_ROLES, [
            'name' => app()->make('Faker\Generator')->text(500),
            'code' => app()->make('Faker\Generator')->text(500),
            'status' => 'DERP'
        ]);
         $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'name' => ['The name may not be greater than 255 characters.'],
                'code' => ['The code may not be greater than 255 characters.'],
                'status' => ['The selected status is invalid.']
            ]
        ]);
        // Test for duplicate
        $existingRole = factory(\ProcessMaker\Model\Role::class)->create([
            'code' => 'TESTROLE'
        ]);
        $response = $this->api('post', self::API_TEST_ROLES, [
            'name' => 'Test Conflict Role',
            'code' => 'TESTROLE',
            'status' => 'ACTIVE'
        ]);
        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'code' => ['The code has already been taken.'],
            ]
        ]);
    }

    public function testSuccessfulRoleCreate()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('post', self::API_TEST_ROLES, [
            'name' => 'Test Role',
            'code' => 'TESTROLE',
            'description' => 'This is a test role',
            'status' => 'ACTIVE'
        ]);
        $response->assertStatus(200);
        // Okay, grab our role from the DB
        $role = Role::where('code', 'TESTROLE')->first();
        $transformed = (new RoleTransformer())->transform($role);
        $response->assertJson($transformed);
    }

    public function testEditRoleSuccess() 
    {
        //create a role, but with a fixed name with a factory method 
        $role = factory(\ProcessMaker\Model\Role::class)->create([
            'code' => 'TESTROLE',
            'uid' => '134A',
            'code' => 'word',
            'name' => 'name',
            'description' => 'sentence',
            'status' => 'ACTIVE'
        //Fetch from database that role with the uid that was created
        $response = $this->api('get', self::API_TEST_ROLES, [
            'uid' => '134A',
        ]);
        //Then call api to change the role name
        //re-fetch from database that role
        //assert role name is now the changed name 

    }
}
//error cases
        //what if a role is no found?
        //what if I pass in a parameter value hat is invalid (such as name)


 