<?php

namespace Tests\Feature;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CasesControllerTest extends TestCase
{
    use RequestHelper;

    const URL_CASES = '/cases/';

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

    public function testShowCaseWithUserWithoutParticipation()
    {
        // Create user admin
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create the main request
        $parentRequest = ProcessRequest::factory()->create([
            'parent_request_id' => null,
        ]);

        // Create request child
        ProcessRequest::factory()->create([
            'parent_request_id' => $parentRequest->id,
        ]);

        // Call the view
        $response = $this->get(route('cases.show', ['case_number' => $parentRequest->case_number]));

        // Check the status
        $response->assertStatus(403);
    }

    public function testShowCaseWithUserAdmin()
    {
        // Create user admin
        $user = User::factory()->create([
            'is_administrator' => true,
        ]);
        $this->actingAs($user);

        // Create the main request
        $parentRequest = ProcessRequest::factory()->create([
            'parent_request_id' => null,
        ]);

        // Create request child
        ProcessRequest::factory()->create([
            'parent_request_id' => $parentRequest->id,
        ]);

        // Call the view
        $response = $this->get(route('cases.show', ['case_number' => $parentRequest->case_number]));

        // Check the status
        $response->assertStatus(200);
    }

    public function testShowCaseWithProcessVersion()
    {
        // Create user admin
        $user = User::factory()->create([
            'is_administrator' => true,
        ]);
        $this->actingAs($user);

        // Create a process
        $bpmnTemplate = Process::getProcessTemplate('OnlyStartElement.bpmn');
        $process = Process::factory()->create([
            'bpmn' => $bpmnTemplate,
        ]);
        $request1 = ProcessRequest::factory()->create([
            'parent_request_id' => null,
            'process_id' => $process->id,
        ]);
        // Update the process
        $newBpmn = trim(Process::getProcessTemplate('SingleTask.bpmn'));
        $route = route('api.' . 'processes' . '.update', [$process->id]);
        $response = $this->apiCall('PUT', $route, [
            'name' => 'test name',
            'description' => 'test description',
            'bpmn' => $newBpmn,
        ]);
        $response->assertStatus(200);
        // Create a request with the new process
        $request2 = ProcessRequest::factory()->create([
            'parent_request_id' => null,
            'process_id' => $process->id,
        ]);
        // Validate the request1
        $response = $this->get(route('cases.show', ['case_number' => $request1->case_number]));
        $response->assertStatus(200);
        $response->assertViewHas('bpmn');
        $this->assertEquals($bpmnTemplate, $response->viewData('bpmn'));
        // Validaet the request2
        $response = $this->get(route('cases.show', ['case_number' => $request2->case_number]));
        $response->assertStatus(200);
        $response->assertViewHas('bpmn');
        $this->assertEquals($newBpmn, $response->viewData('bpmn'));
    }

    public function testShowCaseWithParticipateUser()
    {
        // Create user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create the main request
        $parentRequest = ProcessRequest::factory()->create([
            'parent_request_id' => null,
        ]);

        // Create request child
        ProcessRequest::factory()->create([
            'parent_request_id' => $parentRequest->id,
        ]);

        // Create the participation
        ProcessRequestToken::factory()->create([
            'process_request_id' => $parentRequest->id,
            'user_id' => $user->id,
        ]);

        // Call the view
        $response = $this->get(route('cases.show', ['case_number' => $parentRequest->case_number]));

        // Check the status
        $response->assertStatus(200);
    }

    /**
     * Test show default summary tab when the status is Error
     * @return void
     */
    public function testCaseErrorWithDataDefaultSummary()
    {
        $process = Process::factory()->create();
        $requestCanceled = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => ['form_input_1' => 'TEST DATA'],
            'status' => 'ERROR',
        ]);
        $response = $this->webCall('GET', self::URL_CASES . $requestCanceled->case_number);
        // Get the URL
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('cases.edit');
    }

    /**
     * Test show default summary tab when the status is InProgress
     * Without custom screen "Request Detail Screen"
     * @return void
     */
    public function testCaseInProgressWithDataDefaultSummary()
    {
        $process = Process::factory()->create();
        $requestCanceled = ProcessRequest::factory()->create([
            'name' => $process->name,
            'process_id' => $process->id,
            'data' => ['form_input_1' => 'TEST DATA'],
            'status' => 'ACTIVE',
        ]);
        $response = $this->webCall('GET', self::URL_CASES . $requestCanceled->case_number);
        // Get the URL
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('cases.edit');
        // When the user goes to summary tab can see: Request In Progress
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
        $response = $this->webCall('GET', self::URL_CASES . $requestCompleted->case_number);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('cases.edit');
        // When the user goes to summary tab can see: No Data Found
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
        $response = $this->webCall('GET', self::URL_CASES . $requestCompleted->case_number);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('cases.edit');
        // When the user goes to summary tab can see: No Data Found
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
        $response = $this->webCall('GET', self::URL_CASES . $requestCompleted->case_number);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('cases.edit');
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
        $response = $this->webCall('GET', self::URL_CASES . $requestActive->case_number);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('cases.edit');
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
        $response = $this->webCall('GET', self::URL_CASES . $requestCanceled->case_number);
        $response->assertStatus(200);
        // Check the correct view is called
        $response->assertViewIs('cases.edit');
        // Check custom detail screen is displayed instead default summary
        $response->assertSee('TEST WITH CUSTOM REQUEST DETAIL SCREEN');
    }
}
