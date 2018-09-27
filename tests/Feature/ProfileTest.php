<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;

class ProfileTest extends TestCase
{
    use RequestHelper;
    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testIndexRoute()
    {

      // get the URL
      $response = $this->apiCall('GET', '/profile');
      // check the correct view is called
      $response->assertViewIs('profile.index');

      $response->assertStatus(200);

    }

      /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testEditRoute()
    {

      $user_uuid = factory(User::class)->create()->uuid_text;
      // get the URL
      $response = $this->apiCall('GET', '/profile/edit');

      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('profile.edit');

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
      $response = $this->apiCall('GET', '/profile'. $user_uuid);

      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('profile.show');

    }
}
