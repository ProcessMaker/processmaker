<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Facades\Hash;

class GroupsTest extends TestCase
{

  use DatabaseTransactions;
  use RequestHelper;

  const API_TEST_URL = '/api/1.0/groups';
  const DEFAULT_PASS = 'password';

  const STRUCTURE = [
      'uuid',
      'name',
      'status',
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
  public function testCreateGroup()
  {
      //Post title duplicated
      $faker = Faker::create();
      $url = self::API_TEST_URL;
      $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
          'name' => 'newgroup',
          'status' => 'ACTIVE'
      ]);

      //Validate the header status code
      $response->assertStatus(201);
  }

  /**
   * Can not create a group with an existing name
   */
  public function testNotCreateGroupWithGroupnameExists()
  {
      factory(Group::class)->create([
          'name' => 'mytestname',
      ]);

      //Post name duplicated
      $faker = Faker::create();
      $response = $this->actingAs($this->user, 'api')->json('POST', self::API_TEST_URL, [
          'name' => 'mytestname'
      ]);

      //Validate the header status code
      $response->assertStatus(422);
      $this->assertArrayHasKey('message', $response->json());
  }

  /**
   * Get a list of Groups without query parameters.
   */
  public function testListGroup()
  {

      Group::query()->delete();

      $faker = Faker::create();

      factory(Group::class, 10)->create();

      $response = $this->actingAs($this->user, 'api')->json('GET', self::API_TEST_URL);

      //Validate the header status code
      $response->assertStatus(200);

      // Verify structure
      $response->assertJsonStructure([
          'data' => ['*' => self::STRUCTURE],
          'meta',
      ]);

      // Verify count
      $this->assertEquals(10, $response->json()['meta']['total']);

  }

  /**
   * Get a list of Group with parameters
   */
  public function testListGroupWithQueryParameter()
  {
      $name = 'mytestname';

      factory(Group::class)->create([
          'name' => $name,
      ]);

      //List Group with filter option
      $perPage = Faker::create()->randomDigitNotNull;
      $query = '?page=1&per_page=' . $perPage . '&order_by=name&order_direction=DESC&filter=' . $name;
      $response = $this->actingAs($this->user, 'api')->json('GET', self::API_TEST_URL . $query);

      //Validate the header status code
      $response->assertStatus(200);

      //verify structure paginate
      $response->assertJsonStructure([
          'data',
          'meta',
      ]);

      // Verify return data
      $this->assertEquals(1, $response->json()['meta']['total']);
      $this->assertEquals('name', $response->json()['meta']['sort_by']);
      $this->assertEquals('DESC', $response->json()['meta']['sort_order']);

  }

  /**
   * Get a group
   */
  public function testGetGroup()
  {
      //get the uuid from the factory
      $group = factory(Group::class)->create()->uuid_text;

      //load api
      $response = $this->apiCall('GET', self::API_TEST_URL. '/' . $group);

      //Validate the status is correct
      $response->assertStatus(200);

      //verify structure
      $response->assertJsonStructure(self::STRUCTURE);
  }

  /**
   * Get a group with the memberships
   */
  // public function testGetGroupIncludeMembership()
  // {
  //     //get the uuid from the factory
  //     $group = factory(Group::class)->create()->uuid_text;
  //
  //     //load api
  //     $response = $this->apiCall('GET', self::API_TEST_URL. '/' . $group . '?include=memberships');
  //
  //     //Validate the status is correct
  //     $response->assertStatus(200);
  //
  //     //verify structure
  //     $response->assertJsonFragment(['memberships']);
  // }

  /**
   * Parameters required for update of group
   */
  public function testUpdateGroupParametersRequired()
  {
      $faker = Faker::create();

      $uuid = factory(Group::class)->create(['name' => 'mytestname'])->uuid_text;
      //The post must have the required parameters
      $url = self::API_TEST_URL . '/' .$uuid;

      $response = $this->apiCall('PUT', $url, [
          'status' => 'updatemytestname'
      ]);

      //Validate the header status code
      $response->assertStatus(422);
  }

  /**
   * Update group in process
   */
  public function testUpdateGroup()
  {
      $faker = Faker::create();

      $url = self::API_TEST_URL . '/' . factory(Group::class)->create()->uuid_text;

      //Load the starting group data
      $verify = $this->apiCall('GET', $url);

      //Post saved success
      $response = $this->actingAs($this->user, 'api')->json('PUT', $url, [
        'name' => 'updatemytestname',
      ]);

      //Validate the header status code
      $response->assertStatus(204);

      //Load the updated group data
      $verify_new = $this->apiCall('GET', $url);

      //Check that it has changed
      $this->assertNotEquals($verify,$verify_new);

  }

  /**
   * Check that the validation wont allow duplicate names
   */
  public function testUpdateGroupTitleExists()
  {
      $group1 = factory(Group::class)->create([
          'name' => 'MyGroupName',
      ]);

      $group2 = factory(Group::class)->create();

      $url = self::API_TEST_URL . '/' . $group2->uuid_text;

      $response = $this->apiCall('PUT', $url, [
          'name' => 'MyGroupName',
      ]);
      //Validate the header status code
      $response->assertStatus(422);
      $response->assertSeeText('The name has already been taken');
  }

  /**
   * Delete group in process
   */
  public function testDeleteGroup()
  {
      //Remove group
      $url = self::API_TEST_URL . '/' . factory(Group::class)->create()->uuid_text;
      $response = $this->apiCall('DELETE', $url);

      //Validate the header status code
      $response->assertStatus(204);
  }

  /**
   * The group does not exist in process
   */
  public function testDeleteGroupNotExist()
  {
      //Group not exist
      $url = self::API_TEST_URL . '/' . factory(Group::class)->make()->uuid_text;
      $response = $this->apiCall('DELETE', $url);

      //Validate the header status code
      $response->assertStatus(405);
  }

}
