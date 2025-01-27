<?php

namespace Tests\Feature;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Facades\WorkflowManager;
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
     * Test email task notification
     */
    public function testEmailTaskNotificationInFormTask()
    {
        $user = User::factory()->create([
            'email_task_notification' => 1,
        ]);
        Auth::login($user);
        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/email_task_notification_process.bpmn'),
        ]);
        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        $response->assertStatus(201);
        // Find the request
        $instance = ProcessRequest::first();
        $task = ProcessRequestToken::where('element_type', 'task')->where('process_id', $process->id)->where('status', 'ACTIVE')->first();
        $this->assertEquals(0, $task->is_emailsent);
        $user = User::where('id', $task->user_id)->first();
        $user->email_task_notification = 1;
        $user->save();
        WorkflowManager::completeTask($process, $instance, $task, []);
        $task = ProcessRequestToken::where('element_type', 'task')->where('process_id', $process->id)->where('status', 'ACTIVE')->first();
        $this->assertEquals(0, $task->is_emailsent);
    }
}
