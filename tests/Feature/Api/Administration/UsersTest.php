<?php

namespace Tests\Feature\Api\Administration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use ProcessMaker\Transformers\UserTransformer;
use Tests\Feature\Api\ApiTestCase;

/**
 * Tests the Users API Endpoints with expected values
 */
class UsersTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_USERS = '/api/1.0/users';

    /**
     * These api endpoints can only work if you are authenticated
     */
    public function testUnauthenticated()
    {
        $response = $this->api('GET', self::API_TEST_USERS);
        $response->assertStatus(401);
    }

    /**
     * Ensure our API endpoint is protected by required permission
     */
    public function testUnauthorized()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => factory(Role::class)->make(),
        ]);
        // No role means it should not be authorized
        $this->auth($user->username, 'password');
        $response = $this->api('GET', self::API_TEST_USERS);
        $response->assertStatus(403);
    }

    /**
     * Verify users listing is working with no filters provided
     */
    public function testUsersListing()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        // Build a sample of 5 users into the system
        $users = factory(User::class, 5)->create();
        // Fetch via API
        $response = $this->api('GET', self::API_TEST_USERS);
        // Verify 200 status code
        $response->assertStatus(200);
        // Grab users
        $data = json_decode($response->getContent(), true);
        // Verify we have a total of 7 results (our 5 plus admin plus our created user)
        $this->assertCount(7, $data['data']);
        $this->assertEquals(7, $data['meta']['total']);
        // Not testing returned data format as we're assuming the single user fetch validates that 
        // output matches transformer
    }

    /**
     * Test to ensure our filters are working and not matching on invalid filter criteria
     */
    public function testUserSearchNoMatches()
    {
        $user = factory(User::class)->create([
            'username' => 'testuser',
            'firstname' => 'Joe',
            'lastname' => 'Biden',
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('GET', self::API_TEST_USERS . '?filter=' . urlencode('invalid'));
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
            'username' => 'testuser',
            'firstname' => 'Joe',
            'lastname' => 'Biden',
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('GET', self::API_TEST_USERS . '?filter=' . urlencode('joe'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(),true);
        // Ensure we have empty results
        $this->assertCount(1, $data['data']);
        $this->assertEquals(1, $data['meta']['total']);
        $this->assertEquals('joe', $data['meta']['filter']);
        // We refetch from the DB to ensure we have all columns from DB when transforming
        $transformed = (new UserTransformer())->transform(User::find($user->id));
        $this->assertEquals($transformed, $data['data'][0]);
    }

    /**
     * Test to ensure we receive a 404 when passing in a uid not found
     */
    public function testUserNotFound()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('get', self::API_TEST_USERS . '/invaliduid');
        $response->assertStatus(404);
    }

    /**
     * Test fetch single user fetch from api
     */
    public function testUserGet()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('get', self::API_TEST_USERS . '/' . $user->uid->toString());
        $response->assertStatus(200);
        // Get our expected transformed user
        $expected = (new UserTransformer())->transform($user);
        // Now verify that's what we got
        $response->assertJson($expected);
    }

}
 