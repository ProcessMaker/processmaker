<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Shared\RequestHelper;

class ScriptsTest extends TestCase
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
      $response = $this->apiCall('GET', '/processes/scripts');
      // check the correct view is called
      $response->assertViewIs('processes.scripts.index');

      $response->assertStatus(200);

    }
}
