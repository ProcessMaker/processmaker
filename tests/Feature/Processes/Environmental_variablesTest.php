<?php

namespace Tests\Feature\Processes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Shared\RequestHelper;

class Environmental_variablesTest extends TestCase
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
      $response = $this->webCall('GET', '/processes/environmental_variables');
      // check the correct view is called
      $response->assertViewIs('processes.environmental_variables.index');

      $response->assertStatus(200);

      $response->assertSee('Environmental Variables');
    }
}
