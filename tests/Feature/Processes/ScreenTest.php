<?php

namespace Tests\Feature\Processes;

use ProcessMaker\Models\Screen;
use Tests\TestCase;
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

    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testEditRoute()
    {
        // get the URL
        $response = $this->webCall('GET', '/processes/screens/' .
            factory(Screen::class)->create()->id . '/edit');

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('processes.screens.edit');
        $response->assertSee('Edit Screen');
    }
}
