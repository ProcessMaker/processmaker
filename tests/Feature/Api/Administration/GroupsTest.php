<?php

namespace Tests\Feature\Api\Administration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Group;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use ProcessMaker\Transformers\GroupTransformer;
use Tests\Feature\Api\ApiTestCase;

/**
 * Tests the Groups API Endpoints with expected values
 */
class GroupsTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_GROUPS = '/api/1.0/groups';

    /**
     * 
     * These api endpoints can only work if you are authenticated
     */
    /*
    public function testUnauthenticated()
    {
        $response = $this->api('GET', self::API_TEST_GROUPS);
        $response->assertStatus(401);
    }
    */

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
        $response = $this->api('GET', self::API_TEST_GROUPS);
        $response->assertStatus(403);
    }
    /**
     * Verify groups listing is working with no filters provided
     */
    public function testGroupsListing()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        // Build a sample of 5 groups into the system
        $roles = factory(Group::class, 5)->create();
        // Fetch via API
        $response = $this->api('GET', self::API_TEST_GROUPS);
        // Verify 200 status code
        $response->assertStatus(200);
        // Grab users
        $data = json_decode($response->getContent(), true);
        // Verify we have a total of 5 results
        $this->assertCount(5, $data['data']);
        $this->assertEquals(5, $data['meta']['total']);
        // Not testing returned data format as we're assuming the single role fetch validates that 
        // output matches transformer
    }

    /**
     * Test to ensure our filters are working and not matching on invalid filter criteria
     */
    public function testGroupSearchNoMatches()
    {
       $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('GET', self::API_TEST_GROUPS . '?filter=' . urlencode('invalid'));
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
        // Now create a group that would match
        $group = factory(Group::class)->create([
            'title' => 'Test Matching Group'
        ]);
        $response = $this->api('GET', self::API_TEST_GROUPS . '?filter=' . urlencode('Matching Gr'));
        $response->assertStatus(200);
        $data = json_decode($response->getContent(),true);
        // Ensure we have empty results
        $this->assertCount(1, $data['data']);
        $this->assertEquals(1, $data['meta']['total']);
        $this->assertEquals('Matching Gr', $data['meta']['filter']);
        // We refetch from the DB to ensure we have all columns from DB when transforming
        $transformed = (new GroupTransformer())->transform(Group::find($group->id));
        $this->assertEquals($transformed, $data['data'][0]);
    }

    /**
     * Test to ensure we receive a 404 when passing in a uid not found
     */
    public function testGroupNotFound()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('get', self::API_TEST_GROUPS . '/invaliduid');
        $response->assertStatus(404);
    }

    /**
     * Test fetch single group fetch from api
     */
    public function testGroupGet()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $group = factory(Group::class)->create();
        $response = $this->api('get', self::API_TEST_GROUPS . '/' . $group->uid);
        $response->assertStatus(200);
        // Get our expected transformed group
        $expected = (new GroupTransformer())->transform($group);
        // Now verify that's what we got
        $response->assertJson($expected);
    }

    /**
     * Test fetch single group fetch from api with one user assigned to group
     */
    public function testGroupGetWithUserAssigned()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $group = factory(Group::class)->create();
        // Create 5 users
        $users = factory(User::class, 5)->create();
        // Iterate through them and add them to the new group
        foreach($users as $user) {
            $user->groups()->attach($group);
            $user->save();
        }
       $response = $this->api('get', self::API_TEST_GROUPS . '/' . $group->uid);
        $response->assertStatus(200);
       // Now ensure that we have 1 user assigned
        $response->assertJson(['total_users' => 5]);
    }

    public function testGroupCreateWithValidationFailure()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('post', self::API_TEST_GROUPS, [
            
        ]);
        // Empty, we should receive validation errors
        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'title' => ['The title field is required.'],
                'status' => ['The status field is required.']
            ]
        ]);
        // Ensure out of bounds checks on validation
        $response = $this->api('post', self::API_TEST_GROUPS, [
            'title' => app()->make('Faker\Generator')->text(500),
            'status' => 'DERP'
        ]);
         $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'title' => ['The title may not be greater than 255 characters.'],
                'status' => ['The selected status is invalid.']
            ]
        ]);
        // Test for duplicate
        $existingGroup = factory(\ProcessMaker\Model\Group::class)->create([
            'title' => 'TESTGROUP'
        ]);
        $response = $this->api('post', self::API_TEST_GROUPS, [
            'title' => 'TESTGROUP',
            'status' => 'ACTIVE'
        ]);
        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'title' => ['The title has already been taken.'],
            ]
        ]);
    }

    public function testSuccessfulGroupCreate()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        $response = $this->api('post', self::API_TEST_GROUPS, [
            'title' => 'Test Group',
            'status' => 'ACTIVE'
        ]);
        $response->assertStatus(200);
        // Okay, grab our group from the DB
        $group = Group::where('title', 'Test Group')->first();
        $transformed = (new GroupTransformer())->transform($group);
        $response->assertJson($transformed);
    }


    public function testSortGroupsByTotalUsers()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');
        // Now, let's create 5 groups
        factory(\ProcessMaker\Model\Group::class, 5)->create();
        // Now create a single group that we'll add to our user
        $group = factory(\ProcessMaker\Model\Group::class)->create([
            'title' => 'Test Group'
        ]);
        // Now, let's attach the group to the user
        $user->groups()->attach($group);
        // Fetch via API
        $response = $this->api('GET', self::API_TEST_GROUPS, [
            'order_by' => 'total_users',
            'order_direction' => 'desc'
        ]);
        // Verify 200 status code
        $response->assertStatus(200);
        // Grab users
        $data = json_decode($response->getContent(), true);
        // Verify we have a total of 6 results
        $this->assertCount(6, $data['data']);
        $this->assertEquals(6, $data['meta']['total']);
        $this->assertEquals('Test Group', $data['data'][0]['title']);
        // Test the other direction
        $response = $this->api('GET', self::API_TEST_GROUPS, [
            'order_by' => 'total_users',
            'order_direction' => 'asc'
        ]);
        // Verify 200 status code
        $response->assertStatus(200);
        // Grab users
        $data = json_decode($response->getContent(), true);
        // Verify we have a total of 6 results
        $this->assertCount(6, $data['data']);
        $this->assertEquals(6, $data['meta']['total']);
        $this->assertEquals('Test Group', $data['data'][5]['title']);
  
    }

    /**
     * Test Delete group
     */
    public function testDeleteGroup()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id'     => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id,
        ]);
        $this->auth($user->username, 'password');

        $group = factory(Group::class)->create();

        $response = $this->api('delete', self::API_TEST_GROUPS . '/' . $group->uid);
        $response->assertStatus(204);

        //validating that the group does not exist
        $existGroup = Group::where('uid', $group->uid)->first();
        $this->assertNull($existGroup);
    }
}



 