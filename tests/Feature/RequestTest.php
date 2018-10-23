<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\ProcessRequest;

class RequestTest extends TestCase
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
      $response = $this->webCall('GET', '/requests');
      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('requests.index');

    }

      /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testEditRoute()
    {

      $Request_id = factory(ProcessRequest::class)->create()->id;
      // get the URL
      $response = $this->webCall('GET', '/requests/'. $Request_id . '/edit');

      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('requests.edit');
    }

     /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testShowRoute()
    {

      $Request_id = factory(ProcessRequest::class)->create()->id;
      // get the URL
      $response = $this->webCall('GET', '/requests/'. $Request_id);

      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('requests.show');
    }
}
