<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use Tests\Feature\Shared\ApiCallWithUser;

class GroupTest extends TestCase
{
    use ApiCallWithUser;
      /**
     * Create initial user
     */
    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }

     /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testIndexRoute()
    {

      // get the URL
      $response = $this->apiCall('GET', '/admin/groups');
      // check the correct view is called
      $response->assertViewIs('admin.groups.index');

      $response->assertStatus(200);

    }
     /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testEditRoute()
    {

      $group_uuid = factory(Group::class)->create()->uuid_text;
      // get the URL
      $response = $this->apiCall('GET', '/admin/groups/'.$group_uuid . '/edit');

      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('admin.groups.edit');

    }
}
