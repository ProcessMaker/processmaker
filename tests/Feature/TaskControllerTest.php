<?php

namespace Tests\Feature;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Helpers\AbeRecipientSignature;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RequestHelper;

    /**
     * Test Process action by email with screen completed
     */
    public function testActionByEmailWithScreen()
    {
        $screen = Screen::factory()->create([
            'id' => 4000,
        ]);
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_with_screen_complete.bpmn'),
        ]);
        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $processAbeRequest = ProcessAbeRequestToken::first();
        $this->assertEquals($screen->id, $processAbeRequest->completed_screen_id);

        $response = $this->webCall(
            'GET',
            'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes'
        );

        // check the correct view is called
        $response->assertViewIs('processes.screens.completedScreen');
        $response->assertStatus(200);
    }

    /**
     * Test Process action by email without screen completed
     */
    public function testActionByEmailWithoutScreen()
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_require_login.bpmn'),
        ]);
        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $processAbeRequest = ProcessAbeRequestToken::first();
        $this->assertEquals(null, $processAbeRequest->completed_screen_id);

        $response = $this->webCall(
            'GET',
            'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes'
        );

        $response->assertSee('Your response has been submitted.');
        $response->assertStatus(200);
    }

    /**
     * Test Process action by email with empty screen completed
     */
    public function testActionByEmailWithScreenEmpty()
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_with_screen_complete_empty.bpmn'),
        ]);
        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $processAbeRequest = ProcessAbeRequestToken::first();
        $this->assertEquals(null, $processAbeRequest->completed_screen_id);

        $response = $this->webCall(
            'GET',
            'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes'
        );

        $response->assertSee('Your response has been submitted.');
        $response->assertStatus(200);
    }

    /*
     * Test Process action by email with require login and without screen completed
    */
    public function testActionByEmailWithRequireLogin()
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_require_login.bpmn'),
        ]);

        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route);

        $processAbeRequest = ProcessAbeRequestToken::first();

        $this->assertEquals(1, $processAbeRequest->require_login);
        $this->assertEquals(null, $processAbeRequest->completed_screen_id);
    }

    /*
     * Test Process action by email without require login and without screen completed
    */
    public function testActionByEmailWithoutRequireLogin()
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_no_require_login.bpmn'),
        ]);
        Screen::factory()->create();

        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $processAbeRequest = ProcessAbeRequestToken::first();

        $this->assertEquals(0, $processAbeRequest->require_login);
        $this->assertEquals(null, $processAbeRequest->completed_screen_id);
    }

    /**
     * Test Process action by email when the token is invalid
     */
    public function testReturnMessageTokenNoFound()
    {
        $token = Faker::create()->uuid;
        $response = $this->webCall(
            'GET',
            'tasks/update_variable/' . $token . '?varName=res&varValue=yes'
        );
        $response->assertSee('Token not found');
        $response->assertStatus(404);
    }

    /**
     * ABE click metadata is nested under abe_responder (not flat top-level keys).
     */
    public function testActionByEmailStoresResponderMetadataNestedInRequestData(): void
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_no_require_login.bpmn'),
        ]);
        Screen::factory()->create();

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $processAbeRequest = ProcessAbeRequestToken::firstOrFail();
        $signedQuery = AbeRecipientSignature::queryString(
            $processAbeRequest->uuid,
            'recipient@example.com',
            55,
            [['id' => 9, 'name' => 'Approvers']]
        );

        $response = $this->webCall(
            'GET',
            'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes&' . $signedQuery
        );
        $response->assertStatus(200);

        $request = ProcessRequest::findOrFail($processAbeRequest->process_request_id);
        $data = $request->data;

        $this->assertSame('yes', $data['res'] ?? null);
        $this->assertArrayHasKey('abe_responder', $data);
        $this->assertIsArray($data['abe_responder']);
        $this->assertSame('recipient@example.com', $data['abe_responder']['email']);
        $this->assertSame(55, $data['abe_responder']['user_id']);
        $this->assertSame([['id' => 9, 'name' => 'Approvers']], $data['abe_responder']['groups']);
        $this->assertArrayHasKey('acted_at', $data['abe_responder']);
        // Must not leak as top-level request-data keys (develop never set these there)
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('acted_at', $data);
        $this->assertArrayNotHasKey('identity_mismatch', $data);
        $this->assertArrayNotHasKey('user_id', $data);
    }
}
