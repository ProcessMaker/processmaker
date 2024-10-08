<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessRequestTokenPolicyTest extends TestCase
{
    use RequestHelper;

    public function testGetScreensFromToken()
    {
        $taskUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $grandChildScreen = Screen::factory()->create([
            'config' => json_decode(file_get_contents(__DIR__ . '/screens/child.json')),
        ]);
        $childScreen = Screen::factory()->create([
            'config' => json_decode(
                str_replace(
                    '"screen-id"',
                    $grandChildScreen->id,
                    file_get_contents(__DIR__ . '/screens/parent.json')
                )
            ),
        ]);
        $parentScreen = Screen::factory()->create([
            'config' => json_decode(
                str_replace(
                    '"screen-id"',
                    $childScreen->id,
                    file_get_contents(__DIR__ . '/screens/parent.json')
                )
            ),
        ]);
        $process = Process::factory()->create([
            'bpmn' => str_replace(
                ['[screen]', '[user]'],
                [$parentScreen->id, $taskUser->id],
                file_get_contents(__DIR__ . '/processes/ScreenPolicy.bpmn')
            ),
        ]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_type' => User::class,
            'assignment_id' => $taskUser->id,
        ]);

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, []);
        $task = ProcessRequestToken::where('user_id', $taskUser->id)->first();

        $url = route('api.tasks.get_screen', [$task, $grandChildScreen]);

        // Try with unauthorized user
        $this->user = $otherUser;
        $response = $this->apiCall('GET', $url);
        $response->assertStatus(403);

        $this->user = $taskUser;
        $response = $this->apiCall('GET', $url);
        $response->assertStatus(200);
        $this->assertEquals('child', $response->json()['config'][0]['name']);
    }

    public function testGetInterstitialNestedScreen()
    {
        $taskUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $formScreen = Screen::factory()->create();

        $nestedScreen = Screen::factory()->create([
            'config' => json_decode(file_get_contents(__DIR__ . '/screens/nested.json')),
        ]);
        $interstitialScreen = Screen::factory()->create([
            'config' => json_decode(
                str_replace(
                    '"screen-id"',
                    $nestedScreen->id,
                    file_get_contents(__DIR__ . '/screens/interstitial.json')
                )
            ),
        ]);
        $process = Process::factory()->create([
            'bpmn' => str_replace(
                ['[formScreen]', '[interstitialScreen]', '[user]'],
                [$formScreen->id, $interstitialScreen->id, $taskUser->id],
                file_get_contents(__DIR__ . '/processes/InterstitialWithNestedScreen.bpmn')
            ),
        ]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_type' => User::class,
            'assignment_id' => $taskUser->id,
        ]);

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, []);
        $task = ProcessRequestToken::where('user_id', $taskUser->id)->first();

        $url = route('api.tasks.get_screen', [$task, $nestedScreen]);

        // Try with unauthorized user
        $this->user = $otherUser;
        $response = $this->apiCall('GET', $url);
        $response->assertStatus(403);

        $this->user = $taskUser;
        $response = $this->apiCall('GET', $url);
        $response->assertStatus(200);
        $this->assertEquals('Screen Interstitial', $response->json()['config'][0]['name']);
    }

    public function createTaskHelper($allowReassignment, $assignedTo)
    {
        ProcessCategory::factory()->create(['is_system' => true]);
        $this->seed(\ProcessMaker\Package\AdvancedUserManager\Database\Seeds\AssignmentProcessSeeder::class);

        $bpmn = file_get_contents(base_path('tests/Feature/Api/bpmnPatterns/SimpleTaskProcess.bpmn'));
        $value = $allowReassignment ? 'true' : 'false';
        $bpmn = str_replace('pm:allowReassignment="false"', 'pm:allowReassignment="' . $value . '"', $bpmn);
        $process = Process::factory()->create(['bpmn' => $bpmn]);

        $url = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $url);

        $task = ProcessRequestToken::where('status', 'ACTIVE')->first();
        $task->user_id = $assignedTo->id;
        $task->save();

        return $task;
    }

    public function testReassignTask()
    {
        $assignedUser = User::factory()->create();
        $reassignToUser = User::factory()->create();
        $task = $this->createTaskHelper(true, $assignedUser);

        $this->user = $assignedUser;
        $response = $this->apiCall('PUT', route('api.tasks.update', $task), [
            'user_id' => $reassignToUser->id,
        ]);
        $response->assertStatus(200);

        $this->assertEquals($reassignToUser->id, $task->refresh()->user_id);
    }

    public function testReassignWithoutTaskSetting()
    {
        $assignedUser = User::factory()->create();
        $reassignToUser = User::factory()->create();
        $task = $this->createTaskHelper(false, $assignedUser);

        $this->user = $assignedUser;
        $response = $this->apiCall('PUT', route('api.tasks.update', $task), [
            'user_id' => $reassignToUser->id,
        ]);
        $response->assertStatus(403);

        $this->assertEquals('Not authorized to reassign this task', $response->json()['message']);
    }

    public function testAdminReassignWithoutTaskSetting()
    {
        $assignedUser = User::factory()->create();
        $reassignToUser = User::factory()->create();
        $adminUser = User::factory()->create([
            'is_administrator' => true,
        ]);
        $task = $this->createTaskHelper(false, $assignedUser);

        $this->user = $adminUser;
        $response = $this->apiCall('PUT', route('api.tasks.update', $task), [
            'user_id' => $reassignToUser->id,
        ]);
        $response->assertStatus(200);

        $this->assertEquals($reassignToUser->id, $task->refresh()->user_id);
    }
}
