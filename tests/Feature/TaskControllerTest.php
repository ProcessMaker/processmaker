<?php

namespace Tests\Feature;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Helpers\AbeRecipientSignature;
use ProcessMaker\Http\Controllers\TaskController;
use ProcessMaker\Models\AnonymousUser;
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
     * ABE click metadata is nested under abe_responder when trackResponder is enabled.
     */
    public function testActionByEmailStoresResponderMetadataNestedInRequestData(): void
    {
        $bpmn = str_replace(
            '&#34;requireLogin&#34;:false',
            '&#34;requireLogin&#34;:false,&#34;trackResponder&#34;:true',
            file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_no_require_login.bpmn')
        );
        $process = Process::factory()->create(['bpmn' => $bpmn]);
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

    /**
     * Without trackResponder, request data must not include abe_responder.
     */
    public function testActionByEmailOmitsResponderMetadataWhenTrackingDisabled(): void
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_no_require_login.bpmn'),
        ]);
        Screen::factory()->create();

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $processAbeRequest = ProcessAbeRequestToken::firstOrFail();
        $response = $this->webCall(
            'GET',
            'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes'
        );
        $response->assertStatus(200);

        $request = ProcessRequest::findOrFail($processAbeRequest->process_request_id);
        $data = $request->data;

        $this->assertSame('yes', $data['res'] ?? null);
        $this->assertArrayNotHasKey('abe_responder', $data);
    }

    /**
     * Require Login + tracking: signed recipient survives login even if the query
     * string is dropped; a different logged-in user sets identity_mismatch.
     */
    public function testRequireLoginPreservesSignedRecipientAndSetsIdentityMismatch(): void
    {
        $bpmn = str_replace(
            '&#34;requireLogin&#34;:true',
            '&#34;requireLogin&#34;:true,&#34;trackResponder&#34;:true',
            file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_require_login.bpmn')
        );
        $process = Process::factory()->create(['bpmn' => $bpmn]);
        Screen::factory()->create();

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $processAbeRequest = ProcessAbeRequestToken::firstOrFail();
        $this->assertEquals(1, $processAbeRequest->require_login);

        $signedEmail = 'original-recipient@example.com';
        $signedQuery = AbeRecipientSignature::queryString(
            $processAbeRequest->uuid,
            $signedEmail,
            55,
            [['id' => 9, 'name' => 'Approvers']]
        );

        $anonymous = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();

        $clickUrl = 'tasks/update_variable/' . $processAbeRequest->uuid
            . '?varName=res&varValue=yes&' . $signedQuery;
        // Do not use webCall() — it re-authenticates as the default test user.
        $redirect = $this->actingAs($anonymous, 'web')->get($clickUrl);
        $redirect->assertRedirect('login');
        $redirect->assertPlainCookie(TaskController::ABE_CLICK_TICKET_COOKIE);

        $ticket = $redirect->getCookie(TaskController::ABE_CLICK_TICKET_COOKIE, false)->getValue();
        $this->assertNotEmpty($ticket);
        $this->assertTrue(Cache::has('abe_click:' . $ticket));

        // Simulate post-login return with query string stripped (session cleared on login form).
        $forwardedUser = User::factory()->create([
            'email' => 'forwarded-user@example.com',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($forwardedUser, 'web')
            ->withUnencryptedCookie(TaskController::ABE_CLICK_TICKET_COOKIE, $ticket)
            ->get('tasks/update_variable/' . $processAbeRequest->uuid);
        $response->assertStatus(200);

        $request = ProcessRequest::findOrFail($processAbeRequest->process_request_id);
        $data = $request->data;
        $responder = $data['abe_responder'] ?? null;

        $this->assertIsArray($responder);
        $this->assertSame($signedEmail, $responder['email']);
        $this->assertSame(55, $responder['user_id']);
        $this->assertSame('forwarded-user@example.com', $responder['authenticated_email']);
        $this->assertSame($forwardedUser->id, $responder['authenticated_user_id']);
        $this->assertTrue($responder['identity_mismatch']);
    }

    /**
     * Failed Require Login restore (expired ticket / missing query) must be a
     * validation error, not HTTP 500 "Error updating variable".
     */
    public function testMissingAbeClickQueryAfterFailedRestoreReturnsValidationError(): void
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_require_login.bpmn'),
        ]);
        Screen::factory()->create();

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $processAbeRequest = ProcessAbeRequestToken::firstOrFail();
        $user = User::factory()->create(['status' => 'ACTIVE']);

        $response = $this->actingAs($user, 'web')
            ->withUnencryptedCookie(TaskController::ABE_CLICK_TICKET_COOKIE, 'expired-or-unknown-ticket')
            ->getJson('tasks/update_variable/' . $processAbeRequest->uuid);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['varName', 'varValue']);
        $response->assertJsonMissing(['message' => 'Error updating variable']);
    }

    /**
     * A leftover abe_click_ticket for ABE token A must not be consumed when hitting
     * update_variable for ABE token B (unsigned / no abe_sig).
     */
    public function testAbeClickTicketCacheSurvivesUuidMismatch(): void
    {
        $bpmn = file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_require_login.bpmn');
        Screen::factory()->create();

        $processA = Process::factory()->create(['bpmn' => $bpmn]);
        $this->apiCall('POST', route('api.process_events.trigger', [$processA->id, 'event' => 'node_1']), []);
        $abeA = ProcessAbeRequestToken::orderByDesc('id')->firstOrFail();

        $processB = Process::factory()->create(['bpmn' => $bpmn]);
        $this->apiCall('POST', route('api.process_events.trigger', [$processB->id, 'event' => 'node_1']), []);
        $abeB = ProcessAbeRequestToken::orderByDesc('id')->firstOrFail();
        $this->assertNotSame($abeA->uuid, $abeB->uuid);

        $anonymous = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();
        $parkUrl = 'tasks/update_variable/' . $abeA->uuid . '?varName=res&varValue=yes';
        $redirect = $this->actingAs($anonymous, 'web')->get($parkUrl);
        $redirect->assertRedirect('login');
        $ticket = $redirect->getCookie(TaskController::ABE_CLICK_TICKET_COOKIE, false)->getValue();
        $this->assertTrue(Cache::has('abe_click:' . $ticket));

        // Short intended URL for a different ABE token (no query) must not consume token A's park.
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $this->actingAs($user, 'web')
            ->withUnencryptedCookie(TaskController::ABE_CLICK_TICKET_COOKIE, $ticket)
            ->getJson('tasks/update_variable/' . $abeB->uuid)
            ->assertStatus(422);

        $this->assertTrue(Cache::has('abe_click:' . $ticket));
        $pending = Cache::get('abe_click:' . $ticket);
        $this->assertSame($abeA->uuid, $pending['abe_uuid'] ?? null);
        $this->assertSame('yes', $pending['query']['varValue'] ?? null);

        // Matching short URL still restores the parked Approve.
        $response = $this->actingAs($user, 'web')
            ->withUnencryptedCookie(TaskController::ABE_CLICK_TICKET_COOKIE, $ticket)
            ->get('tasks/update_variable/' . $abeA->uuid);
        $response->assertStatus(200);

        $request = ProcessRequest::findOrFail($abeA->process_request_id);
        $this->assertSame('yes', $request->data['res'] ?? null);
        $this->assertFalse(Cache::has('abe_click:' . $ticket));
    }

    /**
     * With Require Login and tracking off, a second click must park the later
     * action (Reject), not revive the first click's parked Approve via the cookie.
     */
    public function testLaterAbeClickWithRequireLoginParksLatestAction(): void
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_require_login.bpmn'),
        ]);
        Screen::factory()->create();

        $this->apiCall('POST', route('api.process_events.trigger', [$process->id, 'event' => 'node_1']), []);
        $abe = ProcessAbeRequestToken::firstOrFail();
        $anonymous = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();

        $first = $this->actingAs($anonymous, 'web')
            ->get('tasks/update_variable/' . $abe->uuid . '?varName=res&varValue=yes');
        $first->assertRedirect('login');
        $firstTicket = $first->getCookie(TaskController::ABE_CLICK_TICKET_COOKIE, false)->getValue();

        $second = $this->actingAs($anonymous, 'web')
            ->withUnencryptedCookie(TaskController::ABE_CLICK_TICKET_COOKIE, $firstTicket)
            ->get('tasks/update_variable/' . $abe->uuid . '?varName=res&varValue=no');
        $second->assertRedirect('login');
        $secondTicket = $second->getCookie(TaskController::ABE_CLICK_TICKET_COOKIE, false)->getValue();
        $this->assertNotSame($firstTicket, $secondTicket);

        $pending = Cache::get('abe_click:' . $secondTicket);
        $this->assertIsArray($pending);
        $this->assertSame('no', $pending['query']['varValue'] ?? null);

        $user = User::factory()->create(['status' => 'ACTIVE']);
        $response = $this->actingAs($user, 'web')
            ->withUnencryptedCookie(TaskController::ABE_CLICK_TICKET_COOKIE, $secondTicket)
            ->get('tasks/update_variable/' . $abe->uuid);
        $response->assertStatus(200);

        $request = ProcessRequest::findOrFail($abe->process_request_id);
        $this->assertSame('no', $request->data['res'] ?? null);
    }

    /**
     * Presence of either action param skips restore so validation owns the values:
     * partial queries and array-valued params must 422, not revive a parked click or 500.
     */
    public function testPartialOrInvalidActionParamsSkipAbeClickRestoreAndReturnValidationError(): void
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_require_login.bpmn'),
        ]);
        Screen::factory()->create();

        $this->apiCall('POST', route('api.process_events.trigger', [$process->id, 'event' => 'node_1']), []);
        $abe = ProcessAbeRequestToken::firstOrFail();
        $anonymous = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();

        $park = $this->actingAs($anonymous, 'web')
            ->get('tasks/update_variable/' . $abe->uuid . '?varName=res&varValue=yes');
        $park->assertRedirect('login');
        $ticket = $park->getCookie(TaskController::ABE_CLICK_TICKET_COOKIE, false)->getValue();
        $this->assertTrue(Cache::has('abe_click:' . $ticket));

        $user = User::factory()->create(['status' => 'ACTIVE']);

        // Partial query: varName present without varValue must not restore the parked Approve.
        $partial = $this->actingAs($user, 'web')
            ->withUnencryptedCookie(TaskController::ABE_CLICK_TICKET_COOKIE, $ticket)
            ->getJson('tasks/update_variable/' . $abe->uuid . '?varName=res');
        $partial->assertStatus(422);
        $partial->assertJsonValidationErrors(['varValue']);
        $partial->assertJsonMissing(['message' => 'Error updating variable']);
        $this->assertTrue(Cache::has('abe_click:' . $ticket));
        $this->assertSame(0, (int) ProcessAbeRequestToken::findOrFail($abe->id)->is_answered);

        // Array-valued varName must 422 from the string rule, not HTTP 500 from a cast.
        $arrayParam = $this->actingAs($user, 'web')
            ->withUnencryptedCookie(TaskController::ABE_CLICK_TICKET_COOKIE, $ticket)
            ->getJson('tasks/update_variable/' . $abe->uuid . '?varName[]=res&varValue=no');
        $arrayParam->assertStatus(422);
        $arrayParam->assertJsonValidationErrors(['varName']);
        $arrayParam->assertJsonMissing(['message' => 'Error updating variable']);
        $this->assertTrue(Cache::has('abe_click:' . $ticket));
        $this->assertSame(0, (int) ProcessAbeRequestToken::findOrFail($abe->id)->is_answered);
    }
}
