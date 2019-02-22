<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Facades\Hash;

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
        'created_at'
    ];

    /**
     * List group memberships
     */

    public function testGetGroupMemberList()
    {
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $response->assertStatus(200);

        $group1 = factory(Group::class)->create(['name' => 'Group that admin belongs to']);
        $group2 = factory(Group::class)->create(['name' => 'Group that other user belongs to']);

        $other_user = factory(User::class)->create(['status' => 'ACTIVE']);

        factory(GroupMember::class)->create([
            'member_type' => User::class,
            'member_id' => $this->user->id,
            'group_id' => $group1->id
        ]);

        factory(GroupMember::class)->create([
            'member_type' => User::class,
            'member_id' => $other_user->id,
            'group_id' => $group2->id
        ]);

        $response = $this->apiCall('GET', self::API_TEST_URL);
        $json = $response->json('data');
        $this->assertCount(2, $json);
        $this->assertEquals('Group that admin belongs to', $json[0]['name']);
        $this->assertEquals('Group that other user belongs to', $json[1]['name']);

        //when user is regular user they can only get the groups that they belong to
        $this->user = $other_user;
        $response = $this->apiCall('GET', self::API_TEST_URL);
        $json = $response->json('data');
        $this->assertCount(1, $json);
        $this->assertEquals('Group that other user belongs to', $json[0]['name']);
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
        $user = factory(User::class)->create();
        $group = factory(Group::class)->create();

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
        $group1 = factory(Group::class)->create();
        $group2 = factory(Group::class)->create();

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
        $group = factory(GroupMember::class)->create()->id;

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
        $url = self::API_TEST_URL . '/' . factory(GroupMember::class)->create()->id;
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
        $url = self::API_TEST_URL . '/' . factory(GroupMember::class)->make()->id;
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
        factory(Group::class, 15)->create(['status' => 'ACTIVE']);
        $user = factory(User::class)->create(['status' => 'ACTIVE']);
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
        $user = factory(User::class)->create(['status' => 'ACTIVE']);
        factory(GroupMember::class, 10)->create(['member_id' => $user->id, 'member_type' => User::class]);
        factory(Group::class, 15)->create(['status' => 'ACTIVE']);
        $response = $this->apiCall('GET', '/group_members_available', [
            'member_id' => $user->id,
            'member_type' => User::class,
        ]);
        $this->assertEquals(15, $response->json('meta')['total']);
        $response->assertStatus(200);
    }

}
