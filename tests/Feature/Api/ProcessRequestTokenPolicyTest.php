<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\ProcessTaskAssignment;

class ProcessRequestTokenPolicyTest extends TestCase
{
    use RequestHelper;

    public function testGetScreensFromToken()
    {

        $taskUser = factory(User::class)->create();
        $otherUser = factory(User::class)->create();

        $grandChildScreen = factory(Screen::class)->create([
            'config' => json_decode(file_get_contents(__DIR__ . '/screens/child.json'))
        ]);
        $childScreen = factory(Screen::class)->create([
            'config' => json_decode(
                str_replace(
                    '"screen-id"',
                    $grandChildScreen->id,
                    file_get_contents(__DIR__ . '/screens/parent.json')
                )
            )
        ]);
        $parentScreen = factory(Screen::class)->create([
            'config' => json_decode(
                str_replace(
                    '"screen-id"',
                    $childScreen->id,
                    file_get_contents(__DIR__ . '/screens/parent.json')
                )
            )
        ]);
        $process = factory(Process::class)->create([
            'bpmn' => str_replace(
                ['[screen]', '[user]'],
                [$parentScreen->id, $taskUser->id],
                file_get_contents(__DIR__ . '/processes/ScreenPolicy.bpmn')
            )
        ]);
        factory(ProcessTaskAssignment::class)->create([
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
}
