<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
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
      $response = $this->webCall('GET', '/profile');
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

      $user_id = factory(User::class)->create()->id;
      // get the URL
      $response = $this->webCall('GET', '/profile/'. $user_id . '/edit');

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

      $user_id = factory(User::class)->create()->id;
      // get the URL
      $response = $this->webCall('GET', '/profile/'. $user_id);

      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('profile.show');
    }

}
