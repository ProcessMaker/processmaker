<?php

namespace Tests\Feature;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

final class TaskControllerTest extends TestCase
{
    use RequestHelper;

    /**
     * Test Process action by email with screen completed
     */
    public function testActionByEmailWithScreen(): void
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
    public function testActionByEmailWithoutScreen(): void
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
    public function testActionByEmailWithScreenEmpty(): void
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
    public function testActionByEmailWithRequireLogin(): void
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
    public function testActionByEmailWithoutRequireLogin(): void
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
    public function testReturnMessageTokenNoFound(): void
    {
        $token = Faker::create()->uuid;
        $response = $this->webCall(
            'GET',
            'tasks/update_variable/' . $token . '?varName=res&varValue=yes'
        );
        $response->assertSee('Token not found');
        $response->assertStatus(404);
    }
}
