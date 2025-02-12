<?php

namespace Tests\Feature;

use Illuminate\Http\Testing\File;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
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
    public function testRequestShowWithCaseNumberNull()
    {
        $category = ProcessCategory::factory()->create([
            'is_system' => true,
        ]);
        $systemProcess = Process::factory()->create([
            'name' => 'some system process',
            'process_category_id' => $category,
        ]);
        // Create the main request
        $parentRequest = ProcessRequest::factory()->create([
            'parent_request_id' => null,
        ]);

        // Create request child
        $childRequest = ProcessRequest::factory()->create([
            'parent_request_id' => $parentRequest->id,
            'process_id' => $systemProcess->id,
            'case_number' => null,
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $childRequest->id);

        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('requests.show');
    }

    /**
     * Test show the request is when the status is Error
     * @return void
     */
    public function testRequestError()
    {
        $process = Process::factory()->create();
        $requestCanceled = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => ['form_input_1' => 'TEST DATA'],
            'status' => 'ERROR',
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $requestCanceled->id);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('requests.show');
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
        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $process_request->id);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('requests.show');
        // Check custom detail screen is displayed instead default summary
        $response->assertSee('TEST WITH CUSTOM REQUEST DETAIL SCREEN');
    }

    /**
     * Test show default summary tab when the status is InProgress
     * Without custom screen "Request Detail Screen"
     * @return void
     */
    public function testRequestInProgressWithDataDefaultSummary()
    {
        $process = Process::factory()->create();
        $requestCanceled = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => ['form_input_1' => 'TEST DATA'],
            'status' => 'ACTIVE',
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $requestCanceled->id);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('requests.show');
        $response->assertSee('Request In Progress');
    }

    /**
     * Test show default summary tab when the status is Completed
     * Without custom screen "Cancel Screen"
     * @return void
     */
    public function testRequestCanceledDefaultSummary()
    {
        $process = Process::factory()->create();
        $requestCompleted = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => [],
            'status' => 'CANCELED',
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $requestCompleted->id);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('requests.show');
        $response->assertSee('No Data Found');
    }

    /**
     * Test show default summary tab when the status is Completed
     * Without end event custom screen "Summary screen"
     * @return void
     */
    public function testRequestCompletedDefaultSummary()
    {
        $process = Process::factory()->create();
        $requestCompleted = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => [],
            'status' => 'COMPLETED',
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $requestCompleted->id);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('requests.show');
        $response->assertSee('No Data Found');
    }

    /**
     * Test show default summary tab when the status is Completed
     * Without end event custom screen "Summary screen"
     * @return void
     */
    public function testRequestCompletedWithDataDefaultSummary()
    {
        $process = Process::factory()->create();
        $requestCompleted = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => ['form_input_1' => 'TEST DATA'],
            'status' => 'COMPLETED',
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $requestCompleted->id);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('requests.show');
        $response->assertSee('TEST DATA');
    }

    /**
     * Test show custom screen in the summary tab when the status is In Progress
     * With custom screen "Request Detail Screen"
     * @return void
     */
    public function testRequestInprogressWithCustomScreenSummaryTab()
    {
        $screen = Screen::factory()->create([
            'type' => 'DISPLAY',
            'config' => $this->screen,
        ]);
        $process = Process::factory()->create([
            'request_detail_screen_id' => $screen->id,
        ]);
        $requestActive = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => ['form_input_1' => 'TEST DATA'],
            'status' => 'ACTIVE',
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $requestActive->id);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('requests.show');
        // Check custom detail screen is displayed instead default summary
        $response->assertSee('TEST WITH CUSTOM REQUEST DETAIL SCREEN');
    }

    /**
     * Test show custom screen in the summary tab when the status is Canceled
     * With custom screen "Cancel Screen"
     * @return void
     */
    public function testRequestCanceledWithCustomScreenSummaryTab()
    {
        $screen = Screen::factory()->create([
            'type' => 'DISPLAY',
            'config' => $this->screen,
        ]);
        $process = Process::factory()->create([
            'cancel_screen_id' => $screen->id,
        ]);
        $requestCanceled = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => ['form_input_1' => 'TEST DATA'],
            'status' => 'CANCELED',
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $requestCanceled->id);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('requests.show');
        // Check custom detail screen is displayed instead default summary
        $response->assertSee('TEST WITH CUSTOM REQUEST DETAIL SCREEN');
    }

    /**
     * Test show custom screen in the summary tab when the status is Completed
     * With custom screen "Summary Screen"
     * @return void
     */
    public function testRequestCompletedWithCustomScreenSummaryTab()
    {
        $screen = Screen::factory()->create([
            'type' => 'DISPLAY',
            'config' => $this->screen,
        ]);

        $user = User::factory()->create();

        $process = Process::factory()->create([
            'bpmn' => str_replace('SCREEN-SUMMARY-ID', $screen->id, file_get_contents(__DIR__ . '/../Fixtures/process_summary_screen_is_showed_when_the_request_is_completed.bpmn')),
            'user_id' => $user->id,
        ]);
        $requestCompleted = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => ['form_input_1' => 'TEST DATA'],
            'status' => 'COMPLETED',
            'user_id' => $user->id,
        ]);
        // Create the participation
        ProcessRequestToken::factory()->create([
            'process_request_id' => $requestCompleted->id,
            'user_id' => $user->id,
            'element_id' => 'node_12',
            'element_type' => 'end_event',
            'status' => 'CLOSED',
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $requestCompleted->id);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('requests.show');
        // Check custom detail screen is displayed instead default summary
        $response->assertSee('TEST WITH CUSTOM REQUEST DETAIL SCREEN');
    }

    public function testShowTheLinkOpenProcessLaunchpad()
    {
        $process = Process::factory()->create([
            'name' => 'SomeOtherProcess',
        ]);
        // Create a request associated with the process
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $request->id);
        $response->assertStatus(200);
        $response->assertSee('Open Process Launchpad');
    }

    public function testNotShowTheLinkOpenProcessLaunchpad()
    {
        $process = Process::factory()->create([
            'name' => 'DocuSignAuthentication',
        ]);
        // Create a request associated with the process
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        // Get the URL
        $response = $this->webCall('GET', '/requests/' . $request->id);
        $response->assertStatus(200);
        $response->assertDontSee('Open Process Launchpad');
    }
}
