<?php

namespace Tests\Feature\Processes;

use ProcessMaker\Models\EnvironmentVariable;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class EnvironmentVariablesTest extends TestCase
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
      $response = $this->webCall('GET', '/processes/environment-variables');
      // check the correct view is called
      $response->assertViewIs('processes.environment-variables.index');

      $response->assertStatus(200);

      $response->assertSee('Environment Variables');
    }

    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testEditRoute()
    {
        // get the URL
        $response = $this->webCall('GET', '/processes/environment-variables/' .
            factory(EnvironmentVariable::class)->create()->id . '/edit');

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('processes.environment-variables.edit');
        $response->assertSee('Edit Environment Variable');
    }
}
