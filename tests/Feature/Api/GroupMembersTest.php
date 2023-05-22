<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class GroupMembersTest extends TestCase
{
    use RequestHelper;

    const API_TEST_URL = '/group_members';

    const STRUCTURE = [
        'id',
        'group_id',
        'member_id',
        'member_type',
        'updated_at',
        'created_at',
    ];

    /**
     * List group memberships
     */
    public function testGetGroupMemberList()
    {
        // Seed our tables.
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);

        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertStatus(200);

        $group1 = Group::factory()->create(['name' => 'Group that admin belongs to']);
        $group2 = Group::factory()->create(['name' => 'Group that other user belongs to']);

        $other_user = User::factory()->create(['status' => 'ACTIVE']);

        GroupMember::factory()->create([
            'member_type' => User::class,
            'member_id' => $this->user->id,
            'group_id' => $group1->id,
        ]);

        GroupMember::factory()->create([
            'member_type' => User::class,
            'member_id' => $other_user->id,
            'group_id' => $group2->id,
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL);
        $json = $response->json('data');
        $this->assertCount(2, $json);
        $this->assertEquals('Group that admin belongs to', $json[0]['name']);
        $this->assertEquals('Group that other user belongs to', $json[1]['name']);

        //user not have permission
        $this->user = $other_user;
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertStatus(403);
    }

    /**
     * Test verify the parameter required for create form
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $response = $this->apiCall('POST', self::API_TEST_URL, []);

        //Validate the header status code
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Create new group successfully
     */
    public function testCreateGroupMembershipForUser()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        $response = $this->apiCall('POST', self::API_TEST_URL, [
            'group_id' => $group->id,
            'member_id' => $user->id,
            'member_type' => User::class,
        ]);

        //Validate the header status code
        $response->assertStatus(201);

        // make sure it saved the relationship
        $related_group = $user->groupMembersFromMemberable()->first()->group;
        $this->assertTrue($related_group->is($group));

        $member_user = $group->groupMembers()->first()->member;
        $this->assertTrue($member_user->is($user));
    }

    public function testCreateGroupMembershipForGroup()
    {
        $this->withoutExceptionHandling();
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        $response = $this->apiCall('POST', self::API_TEST_URL, [
            'group_id' => $group1->id,
            'member_id' => $group2->id,
            'member_type' => Group::class,
        ]);

        //Validate the header status code
        $response->assertStatus(201);

        // make sure it saved the relationship
        $related_group = $group1->groupMembers()->first()->member;
        $this->assertTrue($related_group->is($group2));

        $member_group = $group2->groupMembersFromMemberable()->first()->group;
        $this->assertTrue($member_group->is($group1));
    }

    /**
     * Get a group
     */
    public function testGetGroupMember()
    {
        //get the id from the factory
        $group = GroupMember::factory()->create()->id;

        //load api
        $response = $this->apiCall('GET', self::API_TEST_URL . '/' . $group);

        //Validate the status is correct
        $response->assertStatus(200);

        //verify structure
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Delete group in process
     */
    public function testDeleteGroupMember()
    {
        //Remove group
        $url = self::API_TEST_URL . '/' . GroupMember::factory()->create()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(204);
    }

    /**
     * The group does not exist in process
     */
    public function testDeleteGroupMemberNotExist()
    {
        //GroupMember not exist
        $url = self::API_TEST_URL . '/' . GroupMember::factory()->make()->id;
        $response = $this->apiCall('DELETE', $url);

        //Validate the header status code
        $response->assertStatus(405);
    }

    /**
     * List group available to assigned
     */
    public function testMembersAllGroupAvailable()
    {
        //The new user does not have groups assigned.
        Group::factory()->count(15)->create(['status' => 'ACTIVE']);
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $response = $this->apiCall('GET', '/group_members_available', [
            'member_id' => $user->id,
            'member_type' => User::class,
        ]);
        $this->assertEquals(15, $response->json('meta')['total']);
        $response->assertStatus(200);
    }

    /**
     * List group available to assigned
     */
    public function testMembersOnlyGroupAvailable()
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);
        GroupMember::factory()->count(10)->create(['member_id' => $user->id, 'member_type' => User::class]);
        Group::factory()->count(15)->create(['status' => 'ACTIVE']);
        $response = $this->apiCall('GET', '/group_members_available', [
            'member_id' => $user->id,
            'member_type' => User::class,
        ]);
        $this->assertEquals(15, $response->json('meta')['total']);
        $response->assertStatus(200);
    }

    /**
     * List group available to assigned
     */
    public function testMembersAllUsersAvailable()
    {
        //The new group does not have groups assigned.
        User::factory()->count(15)->create(['status' => 'ACTIVE']);
        $group = Group::factory()->create(['status' => 'ACTIVE']);
        $count = User::nonSystem()->where('status', 'ACTIVE')->count();
        $response = $this->apiCall('GET', '/user_members_available', [
            'group_id' => $group->id,
        ]);
        $this->assertEquals($count, $response->json('meta')['total']);
        $response->assertStatus(200);
    }

    /**
     * List group available to assigned
     */
    public function testMembersOnlyUsersAvailable()
    {
        //The new group does not have groups assigned.
        $group = Group::factory()->create(['status' => 'ACTIVE']);
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => User::factory()->create(['status' => 'ACTIVE'])->getKey(),
            'member_type' => User::class,
        ]);
        User::factory()->count(15)->create(['status' => 'ACTIVE']);

        $count = User::nonSystem()->where('status', 'ACTIVE')->count() - 1;
        $response = $this->apiCall('GET', '/user_members_available', [
            'group_id' => $group->id,
        ]);
        $this->assertEquals($count, $response->json('meta')['total']);
        $response->assertStatus(200);
    }
}
