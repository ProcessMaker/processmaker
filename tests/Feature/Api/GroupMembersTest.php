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
  public function testCreateGroupMembership()
  {

      $response = $this->actingAs($this->user, 'api')->json('POST', self::API_TEST_URL, [
          'group_uuid' => factory(Group::class)->create()->uuid_text,
          'member_uuid' => factory(User::class)->create()->uuid_text,
          'member_type' => 'user',
      ]);

      //Validate the header status code
      $response->assertStatus(201);
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
      $response->assertJsonStructure(['data' => self::STRUCTURE]);
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
