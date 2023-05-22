<?php

namespace Tests\Feature\Processes;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

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
        $response = $this->webCall('GET', '/designer/scripts');
        $response->assertStatus(200);
        $response->assertViewIs('processes.scripts.index');
        $response->assertSee('Scripts');
    }
}
