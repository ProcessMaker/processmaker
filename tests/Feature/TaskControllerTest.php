<?php

namespace Tests\Feature;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RequestHelper;

    public function testShowScreen()
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
    /*
     * Test Process ABE With require login
    */
    public function testRequireLogin()
    {

        $user = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->first();
        Auth::login($user);

        $process = Process::factory()->create([
            'bpmn' => file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_require_login.bpmn'),
        ]);

        // Start a request
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route);

        $instance = ProcessRequest::first();
        $task = ProcessRequestToken::where('element_name', 'Form Task')
        ->orderBy('id', 'desc')
        ->first();

        $processAbeRequest = ProcessAbeRequestToken::where('process_request_id', $instance->getKey())
        ->where('process_request_token_id', $task->getKey())
        ->first();

        $response = $this->webCall(
            'GET',
            'tasks/update_variable/' . $processAbeRequest->uuid . '?varName=res&varValue=yes'
        );
        
        $loginView = empty(config('app.login_view')) ? 'auth.login' : config('app.login_view');
        // check the correct view is called
        $response->assertViewIs($loginView);
        $response->assertStatus(200);
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
