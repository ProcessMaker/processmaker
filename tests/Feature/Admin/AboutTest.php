<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;

class AboutTest extends TestCase
{
    use RequestHelper;
    
    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testIndexRoute()
    {
      // user without any permissions
      $this->user = factory(User::class)->create();

      // get the URL
      $response = $this->webCall('GET', '/admin/about');
      // check the correct view is called
      $response->assertStatus(200);
      $response->assertViewIs('admin.about.index');


    }
}
