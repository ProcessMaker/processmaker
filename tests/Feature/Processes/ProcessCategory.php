<?php

namespace Tests\Feature;

use Tests\TestCase;
use ProcessMaker\Models\ProcessCategory;
use Tests\Feature\Shared\RequestHelper;

class ProcessCategoryTest extends TestCase
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
        $response = $this->webCall('GET', '/processes/categories');
        // check the correct view is called
        $response->assertViewIs('processes.categories.index');

        $response->assertStatus(200);
        $response->assertSee('Process Categories');

    }

    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testEditRoute()
    {
        // get the URL
        $response = $this->webCall('GET', '/processes/categories/' .
            factory(ProcessCategory::class)->create()->id . '/edit');

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('processes.categories.edit');
        $response->assertSee('Edit Process Category');
    }
}
