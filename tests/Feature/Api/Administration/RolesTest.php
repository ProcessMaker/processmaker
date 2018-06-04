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



}



 