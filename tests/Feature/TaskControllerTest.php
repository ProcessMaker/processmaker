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

class TaskControllerTest extends TestCase
{
    use RequestHelper;

    public function testActionBYEmailWithScreen()
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process.bpmn'),
        ]);
        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $instance = ProcessRequest::first();
        $task = ProcessRequestToken::where('element_name', 'Form Task')->first();

        $processAbeRequest = ProcessAbeRequestToken::factory()->create([
            'process_request_id' => $instance->getKey(),
            'process_request_token_id' => $task->getKey(),
        ]);

        $response = $this->webCall(
            'GET',
            'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes'
        );

        // check the correct view is called
        $response->assertViewIs('processes.screens.completedScreen');
        $response->assertStatus(200);
    }

    public function testActionBYEmailWithoutScreen()
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_require_login.bpmn'),
        ]);
        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $instance = ProcessRequest::first();
        $task = ProcessRequestToken::where('element_name', 'Form Task')->first();

        $processAbeRequest = ProcessAbeRequestToken::factory()->create([
            'process_request_id' => $instance->getKey(),
            'process_request_token_id' => $task->getKey(),
            'completed_screen_id' => null,
        ]);

        $response = $this->webCall(
            'GET',
            'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes'
        );

        $response->assertSee('Your response has been submitted.');
        $response->assertStatus(200);
    }

    /*
     * Test Process ABE With require login
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
    }

    /*
     * Test Process ABE Without require login
    */
    public function testActionByEmailWithoutRequireLogin()
    {
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process.bpmn'),
        ]);
        Screen::factory()->create();

        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $processAbeRequest = ProcessAbeRequestToken::first();

        $this->assertEquals(0, $processAbeRequest->require_login);
    }

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
}
