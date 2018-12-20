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
     * Test that admin users can vue all requests
     *
     * @return void
     */
    public function testRequestAllRouteAsAdmin()
    {
        $this->user = factory(User::class)->create();
        $request = factory(ProcessRequest::class)->create();

        $response = $this->webCall('GET', '/requests/' . $request->id);
        $response->assertStatus(403);

        $this->user->is_administrator = true;
        $this->user->save();
        $response = $this->webCall('GET', '/requests/' . $request->id);
        
        $response->assertStatus(200);
        
        // check the correct view is called
        $response->assertViewIs('requests.show');
    }

    /**
     * Test that the assigned user can vue the request
     *
     * @return void
     */
    public function testShowRouteForUser()
    {
        $this->user = factory(User::class)->create();
        $request = factory(ProcessRequest::class)->create();

        $response = $this->webCall('GET', '/requests/' . $request->id);
        $response->assertStatus(403);

        $request->update(['user_id' => $this->user->id]);
        // $request->refresh();

        $response = $this->webCall('GET', '/requests/' . $request->id);
        $response->assertStatus(200);

        // check the correct view is called
        $response->assertViewIs('requests.show');
    }

    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testShowCancelRoute()
    {
        $Request_id = factory(ProcessRequest::class)->create()->id;
        // get the URL
        $response = $this->webCall('GET', '/requests/' . $Request_id);

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('requests.show');
        $response->assertSee('Cancel Request');
    }

    public function testShowRouteWithAssignedUser()
    {
        $this->user = factory(User::class)->create();

        $request_id = factory(ProcessRequest::class)->create([
            'user_id' => $this->user->id
        ])->id;

        $response = $this->webCall('GET', '/requests/' . $request_id);
        $response->assertStatus(200);
    }

    public function testShowRouteWithAdministrator()
    {
        $this->user = factory(User::class)->create([
            'is_administrator' => true,
        ]);

        $request_id = factory(ProcessRequest::class)->create()->id;

        $response = $this->webCall('GET', '/requests/' . $request_id);
        $response->assertStatus(200);
    }
}
