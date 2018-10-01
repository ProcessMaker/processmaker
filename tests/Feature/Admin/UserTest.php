<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;

class UserTest extends TestCase
{
  use RequestHelper;


  /**
   * Create initial user
   */
  protected function setUp()
  {
      parent::setUp();
      $this->user = factory(User::class)->create();
  }

    /**
     * Test to make sure the controller and route work wiht the view
     *
     * @return void
     */
    public function testIndexRoute()
    {

      // get the URL
      $response = $this->apiCall('GET', '/admin/users');
      // check the correct view is called
      $response->assertViewIs('admin.users.index');

      $response->assertStatus(200);

    }

    /**
     * Test to make sure the controller and route work wiht the view
     *
     * @return void
     */
    public function testEditRoute()
    {

      $user_uuid = factory(User::class)->create()->uuid_text;
      // get the URL
      $response = $this->apiCall('GET', '/admin/users/'.$user_uuid . '/edit');

      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('admin.users.edit');

    }

    /**
     * Test to make sure the controller and route work wiht the view
     *
     * @return void
     */
    public function testCreateRoute()
    {
      // get the URL
      $response = $this->webCall('GET', '/admin/users/create');
      
      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('admin.users.create');

    }
    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testShowRoute()
    {
      $user_uuid = factory(User::class)->create()->uuid_text;
      // get the URL
      $response = $this->apiCall('GET', '/admin/users/'. $user_uuid);

      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('admin.users.show');

    }
    
}
