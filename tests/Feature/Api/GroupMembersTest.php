<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use Tests\TestCase;
use Tests\Feature\Shared\ApiCallWithUser;
use Illuminate\Support\Facades\Hash;

class GroupMembersTest extends TestCase
{

  use DatabaseTransactions;
  use ApiCallWithUser;

  const API_TEST_URL = '/api/1.0/group_members';
  const DEFAULT_PASS = 'password';

  const STRUCTURE = [
      'uuid',
      'group_uuid',
      'member_uuid',
      'member_type',
      'updated_at',
      'created_at'
  ];

  /**
   * Create group
   */
  protected function setUp()
  {
      parent::setUp();
      $this->user = factory(User::class)->create([
          'password' => Hash::make(self::DEFAULT_PASS),
      ]);
  }

  /**
   * Test verify the parameter required for create form
   */
  public function testNotCreatedForParameterRequired()
  {
      //Post should have the parameter required
      $response = $this->actingAs($this->user, 'api')->json('POST', self::API_TEST_URL, []);

      //Validate the header status code
      $response->assertStatus(422);
      $this->assertArrayHasKey('message', $response->json());
  }

  /**
   * Create new group successfully
   */
  public function testCreateGroupMembershipForUser()
  {
      GroupMember::query()->delete();
      $user = factory(User::class)->create();
      $group = factory(Group::class)->create();

      $response = $this->actingAs($this->user, 'api')->json('POST', self::API_TEST_URL, [
          'group_uuid' => $group->uuid_text, 
          'member_uuid' => $user->uuid_text,
          'member_type' => User::class,
      ]);
      
      //Validate the header status code
      $response->assertStatus(201);
      
      // make sure it saved the relationship
      $related_group = $user->memberships()->first()->group;
      $this->assertTrue($related_group->is($group));

      $member_user = $group->members()->first()->member;
      $this->assertTrue($member_user->is($user));
  }
  
  public function testCreateGroupMembershipForGroup()
  {
      GroupMember::query()->delete();
      $group1 = factory(Group::class)->create();
      $group2 = factory(Group::class)->create();

      $response = $this->actingAs($this->user, 'api')->json('POST', self::API_TEST_URL, [
          'group_uuid' => $group1->uuid_text, 
          'member_uuid' => $group2->uuid_text,
          'member_type' => Group::class,
      ]);
      
      //Validate the header status code
      $response->assertStatus(201);

      // make sure it saved the relationship
      $related_group = $group1->members()->first()->member;
      $this->assertTrue($related_group->is($group2));

      $member_group = $group2->memberships()->first()->group;
      $this->assertTrue($member_group->is($group1));
  }

  /**
   * Get a group
   */
  public function testGetGroupMember()
  {
      //get the uuid from the factory
      $group = factory(GroupMember::class)->create()->uuid_text;

      //load api
      $response = $this->apiCall('GET', self::API_TEST_URL. '/' . $group);

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
      $url = self::API_TEST_URL . '/' . factory(GroupMember::class)->create()->uuid_text;
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
      $url = self::API_TEST_URL . '/' . factory(GroupMember::class)->make()->uuid_text;
      $response = $this->apiCall('DELETE', $url);

      //Validate the header status code
      $response->assertStatus(405);
  }

}
