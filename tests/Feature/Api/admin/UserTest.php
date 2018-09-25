<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\User;

class UserTest extends TestCase
{

  public $user;

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
      $response = $this->actingAs($this->user)->get('/admin/users');
      // check the correct view is called
      // dd($this->user);
      // $response->assertViewIs('admin.users.index');

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
      $response = $this->actingAs($this->user)->get('/admin/users/'.$user_uuid);
      // check the correct view is called
      $response->assertViewIs('admin.users.edit');

      $response->assertStatus(200);

    }
}
