<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;

class AboutTest extends TestCase
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
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testIndexRoute()
    {

      // get the URL
      $response = $this->apiCall('GET', '/admin/about');
      // check the correct view is called
      $response->assertViewIs('admin.about.index');

      $response->assertStatus(200);

    }
}
