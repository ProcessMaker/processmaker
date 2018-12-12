<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\Permission;
use \PermissionSeeder;
use Illuminate\Http\Testing\File;

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

    public function testShowMediaFiles()
    {
        $process_request = factory(ProcessRequest::class)->create();
        $file_1 = $process_request->addMedia(File::image('photo1.jpg'))->toMediaCollection();
        $file_2 = $process_request->addMedia(File::image('photo2.jpg'))->toMediaCollection();
        $file_3 = $process_request->addMedia(File::image('photo3.jpg'))->toMediaCollection();

        $response = $this->webCall('GET', '/requests/' . $process_request->id);
        // Full request->getMedia payload is sent for Vue, so assert some HTML also
        $response->assertSee('photo2.jpg</a>');
        $response->assertSee('photo3.jpg</a>');
        $response->assertSee('photo1.jpg</a>');
    }
}
