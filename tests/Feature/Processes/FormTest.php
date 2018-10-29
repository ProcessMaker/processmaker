<?php

namespace Tests\Feature\Processes;

use ProcessMaker\Models\Form;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class FormTest extends TestCase
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
      $response = $this->webCall('GET', '/processes/forms');
      // check the correct view is called
      $response->assertViewIs('processes.forms.index');

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
        $response = $this->webCall('GET', '/processes/forms/' .
            factory(Form::class)->create()->id . '/edit');

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('processes.forms.edit');
        $response->assertSee('Edit Forms');
    }
}
