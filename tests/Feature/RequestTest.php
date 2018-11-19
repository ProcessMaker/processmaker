<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\Permission;
use \PermissionSeeder;

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
     * Test to make sure the controller and route work with the view and show_all_requests permissions
     *
     * @return void
     */
    public function testRequestAllRouteWithShowAllRequestsPermission()
    {
      (new PermissionSeeder)->run($this->user);
      factory(PermissionAssignment::class)->create([
            'assignable_type' => get_class($this->user),
            'assignable_id' => $this->user->id,
            'permission_id' => Permission::byGuardName('show_all_requests')
        ]);
      $this->user->giveDirectPermission('show_all_requests');
      // get the URL
      $response = $this->webCall('GET', '/requests/all');
      $response->assertStatus(200);
      // check the correct view is called
      $response->assertViewIs('requests.index');

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
