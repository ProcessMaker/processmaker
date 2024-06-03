<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Http\Testing\File;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use RequestHelper;

    protected $screen = [
        'name' => 'TICKET-1234 Display',
        'items' => [
            [
                'label' => 'Rich Text',
                'config' => [
                    'icon' => 'fas fa-pencil-ruler',
                    'label' => null,
                    'content' => '<h1>TEST WITH CUSTOM REQUEST DETAIL SCREEN</h1>',
                    'interactive' => true,
                    'renderVarHtml' => false,
                ],
                'component' => 'FormHtmlViewer',
                'editor-control' => 'FormHtmlEditor',
                'editor-component' => 'FormHtmlEditor',
            ],
        ],
    ];

    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testIndexRoute()
    {
        // get the URL
        $response = $this->webCall('GET', '/cases');
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
        $this->user = User::factory()->create();
        $request = ProcessRequest::factory()->create();

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
        $this->user = User::factory()->create();
        $request = ProcessRequest::factory()->create();

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
        $Request_id = ProcessRequest::factory()->create()->id;
        // get the URL
        $response = $this->webCall('GET', '/requests/' . $Request_id);
        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('requests.show');
        $response->assertSee('Requested By');
    }

    public function testShowRouteWithAssignedUser()
    {
        $this->user = User::factory()->create();

        $request_id = ProcessRequest::factory()->create([
            'user_id' => $this->user->id,
        ])->id;

        $response = $this->webCall('GET', '/requests/' . $request_id);
        $response->assertStatus(200);
    }

    public function testShowRouteWithAdministrator()
    {
        $this->user = User::factory()->create([
            'is_administrator' => true,
        ]);

        $request_id = ProcessRequest::factory()->create()->id;

        $response = $this->webCall('GET', '/requests/' . $request_id);
        $response->assertStatus(200);
    }

    public function testShowMediaFiles()
    {
        $process_request = ProcessRequest::factory()->create();
        $file_1 = $process_request
            ->addMedia(File::image('photo1.jpg'))
            ->withCustomProperties(['data_name' => 'test'])
            ->toMediaCollection();
        $file_2 = $process_request
            ->addMedia(File::image('photo2.jpg'))
            ->withCustomProperties(['data_name' => 'test'])
            ->toMediaCollection();
        $file_3 = $process_request
            ->addMedia(File::image('photo3.jpg'))
            ->withCustomProperties(['data_name' => 'test'])
            ->toMediaCollection();

        $response = $this->webCall('GET', '/requests/' . $process_request->id);
        // Full request->getMedia payload is sent for Vue, so assert some HTML also
        $response->assertSee('photo2.jpg</a>', false);
        $response->assertSee('photo3.jpg</a>', false);
        $response->assertSee('photo1.jpg</a>', false);
    }

    public function testCompletedCount()
    {
        $completed1 = ProcessRequest::factory()->create([
            'status' => 'COMPLETED',
        ]);
        $completed2 = ProcessRequest::factory()->create([
            'status' => 'COMPLETED',
        ]);
        $canceled = ProcessRequest::factory()->create([
            'status' => 'CANCELED',
        ]);

        $response = $this->apiCall('GET', '/requests?total=true&pmql=(status = "Completed")');
        $response->assertJson(['meta' => ['total' => 2]]);
    }

    /**
     * Test show default summary tab
     * @return void
     */
    public function testShowDefaultSummaryTab()
    {
        $process = Process::factory()->create();
        $process_request = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => ['form_input_1' => 'TEST DATA'],
        ]);
        // get the URL
        $response = $this->webCall('GET', '/requests/' . $process_request->id);

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('requests.show');
        // check custom detail screen is not displayed instead default summary
        $response->assertDontSee('TEST WITH CUSTOM REQUEST DETAIL SCREEN');
    }

    /**
     * Test show custom request detail screen summary tab
     * @return void
     */
    public function testShowCustomRequestDetailScreenSummaryTab()
    {
        $screen = Screen::factory()->create([
            'type' => 'DISPLAY',
            'config' => $this->screen,
        ]);
        $process = Process::factory()->create([
            'request_detail_screen_id' => $screen->id,
        ]);
        $process_request = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => ['form_input_1' => 'TEST DATA'],
        ]);
        // get the URL
        $response = $this->webCall('GET', '/requests/' . $process_request->id);

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('requests.show');
        // check custom detail screen is displayed instead default summary
        $response->assertSee('TEST WITH CUSTOM REQUEST DETAIL SCREEN');
    }
}
