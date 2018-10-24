<?php

namespace Tests\Feature\Processes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Shared\RequestHelper;

class ScreenTest extends TestCase
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
      $response = $this->webCall('GET', '/processes/screens');
      // check the correct view is called
      $response->assertViewIs('processes.screens.index');

      $response->assertStatus(200);

    }
}
