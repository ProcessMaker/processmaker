<?php

namespace Tests\Feature;

use Carbon\Carbon;
use ProcessMaker\Http\Controllers\CasesController;
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

    /**
     * Test getting stage case with an invalid case number.
     *
     * @return void
     */
    public function testGetStageCaseWithInvalidCaseNumber()
    {
        // Call the API endpoint with a case number that does not exist
        $response = $this->apiCall('GET', '/cases' . '/stages_bar');
        // Assert the response status and structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'parentRequest',
                'requestCount',
                'all_stages',
                'current_stage',
                'stages_per_case',
            ]);
        $this->assertNotEmpty($response->json('stages_per_case'));
        $expectedStages = [
            [
                'id' => 0,
                'name' => 'In Progress',
                'status' => 'In Progress',
                'completed_at' => '',
            ],
            [
                'id' => 0,
                'name' => 'Completed',
                'status' => 'Pending',
                'completed_at' => '',
            ],
        ];
        $response->assertStatus(200)
            ->assertJsonStructure([
                'parentRequest',
                'requestCount',
                'all_stages',
                'current_stage',
                'stages_per_case',
            ])
            ->assertJsonPath('stages_per_case', $expectedStages);
    }

    /**
     * Test getting stage case with a valid case number.
     *
     * @return void
     */
    public function testGetStageCaseWithValidCaseNumber()
    {
        $stagesData = [
            [
                'id' => 1,
                'name' => 'Request Send',
                'order' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Request Reviewed',
                'order' => 2,
            ],
            [
                'id' => 3,
                'name' => 'Manager Reviewed',
                'order' => 3,
            ],
        ];
        // Create a new process and save stages as JSON
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'stages' => json_encode($stagesData),
        ]);
        // Create a parent request
        $parentRequest = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'completed_at' => null,
            'parent_request_id' => null, // This is a parent request
        ]);

        // Create a child request
        ProcessRequest::factory()->create([
            'process_id' => $parentRequest->process_id,
            'case_number' => $parentRequest->case_number,
            'status' => 'ACTIVE',
            'completed_at' => null,
            'parent_request_id' => $parentRequest->id, // This is a child request
        ]);
        ProcessRequestToken::factory()->create([
            'process_id' => $parentRequest->process_id,
            'process_request_id' => $parentRequest->id,
            'completed_at' => '2025-05-01 21:24:24',
            'status' => 'CLOSED',
            'stage_id' => $stagesData[0]['id'],
            'stage_name' => $stagesData[0]['name'],
        ]);
        ProcessRequestToken::factory()->create([
            'process_id' => $parentRequest->process_id,
            'process_request_id' => $parentRequest->id,
            'status' => 'ACTIVE',
            'completed_at' => null,
            'stage_id' => $stagesData[1]['id'],
            'stage_name' => $stagesData[1]['name'],
        ]);

        // Call the API endpoint
        $response = $this->apiCall('GET', '/cases' . '/stages_bar' . '/' . $parentRequest->case_number);

        // Assert the response status and structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'parentRequest' => [
                    'id',
                    'case_number',
                    'status',
                    'completed_at',
                ],
                'requestCount',
                'all_stages',
                'current_stage',
                'stages_per_case',
            ]);
        $this->assertNotEmpty($response->json('stages_per_case'));
        $expectedStages = [
            [
                'id' => 1,
                'name' => 'Request Send',
                'status' => 'Done',
                'completed_at' => '2025-05-01T21:24:24+00:00',
            ],
            [
                'id' => 2,
                'name' => 'Request Reviewed',
                'status' => 'In Progress',
                'completed_at' => '',
            ],
            [
                'id' => 3,
                'name' => 'Manager Reviewed',
                'status' => 'Pending',
                'completed_at' => '',
            ],
        ];
        $response->assertStatus(200)
            ->assertJsonStructure([
                'parentRequest' => [
                    'id',
                    'case_number',
                    'status',
                    'completed_at',
                ],
                'requestCount',
                'all_stages',
                'current_stage',
                'stages_per_case',
            ])
            ->assertJsonPath('stages_per_case', $expectedStages);
    }

    /**
     * Test getting stage case with a valid case number when the process does not have stages
     *
     * @return void
     */
    public function testGetStageCaseWithValidCaseNumberWithoutProcessStages()
    {
        // Create a new process and save stages as JSON
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'stages' => null,
        ]);
        // Create a parent request
        $parentRequest = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'completed_at' => null,
            'parent_request_id' => null, // This is a parent request
        ]);

        // Create a child request
        ProcessRequest::factory()->create([
            'process_id' => $parentRequest->process_id,
            'case_number' => $parentRequest->case_number,
            'status' => 'ACTIVE',
            'completed_at' => null,
            'parent_request_id' => $parentRequest->id, // This is a child request
        ]);
        ProcessRequestToken::factory()->create([
            'process_id' => $parentRequest->process_id,
            'process_request_id' => $parentRequest->id,
            'completed_at' => '2025-05-01 21:24:24',
            'status' => 'CLOSED',
        ]);
        ProcessRequestToken::factory()->create([
            'process_id' => $parentRequest->process_id,
            'process_request_id' => $parentRequest->id,
            'status' => 'ACTIVE',
            'completed_at' => null,
        ]);

        // Call the API endpoint
        $response = $this->apiCall('GET', '/cases' . '/stages_bar' . '/' . $parentRequest->case_number);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'parentRequest',
                'requestCount',
                'all_stages',
                'current_stage',
                'stages_per_case',
            ]);
        $this->assertNotEmpty($response->json('stages_per_case'));
    }

    /**
     * Test getting stage case with a valid case number when the process does not have stages
     *
     * @return void
     */
    public function testGetStageCaseWithValidCaseNumberWithoutTask()
    {
        $stagesData = [
            [
                'id' => 1,
                'name' => 'Request Send',
                'order' => 2,
            ],
            [
                'id' => 2,
                'name' => 'Request Reviewed',
                'order' => 1,
            ],
        ];
        // Create a new process and save stages as JSON
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'stages' => json_encode($stagesData),
        ]);
        // Create a parent request
        $parentRequest = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'completed_at' => null,
            'parent_request_id' => null, // This is a parent request
        ]);

        // Create a child request
        ProcessRequest::factory()->create([
            'process_id' => $parentRequest->process_id,
            'case_number' => $parentRequest->case_number,
            'status' => 'ACTIVE',
            'completed_at' => null,
            'parent_request_id' => $parentRequest->id, // This is a child request
        ]);

        // Call the API endpoint
        $response = $this->apiCall('GET', '/cases' . '/stages_bar' . '/' . $parentRequest->case_number);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'parentRequest',
                'requestCount',
                'all_stages',
                'current_stage',
                'stages_per_case',
            ]);
        $this->assertNotEmpty($response->json('stages_per_case'));
    }
}
