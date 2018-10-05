<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
      $response = $this->apiCall('GET', '/requests');
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

      $Request_uuid = factory(ProcessRequest::class)->create()->uuid_text;
      // get the URL
      $response = $this->apiCall('GET', '/requests/'. $Request_uuid . '/edit');

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

      $Request_uuid = factory(ProcessRequest::class)->create()->uuid_text;
      // get the URL
      $response = $this->apiCall('GET', '/requests/'. $Request_uuid);

      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('requests.show');
    }
}
