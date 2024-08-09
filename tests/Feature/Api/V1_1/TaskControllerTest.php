<?php

namespace Tests\Feature\Api\V1_1;

use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RequestHelper;

    protected $taskController;

    public function testShow()
    {
        $task = ProcessRequestToken::factory()->create();
        $response = $this->apiCall('GET', route('api.1.1.tasks.show', $task->id));
        $response->assertStatus(200)
            ->assertJson(['id' => $task->id]);
    }

    public function testShowScreen()
    {
        $content = file_get_contents(
            __DIR__ . '/Fixtures/nested_screen_process.json'
        );
        ImportProcess::dispatchSync($content);
        $request = ProcessRequest::factory()->create([
            'process_id' => Process::where('name', 'nested screen test')->first()->id,
        ]);
        $task = ProcessRequestToken::factory()->create([
            'element_type' => 'task',
            'element_name' => 'Task 1',
            'element_id' => 'node_2',
            'process_id' => Process::where('name', 'nested screen test')->first()->id,
            'process_request_id' => $request->id,
        ]);
        $response = $this->apiCall('GET', route('api.1.1.tasks.show.screen', $task->id) . '?include=screen,nested');
        $this->assertNotNull($response->json());
        $this->assertIsArray($response->json());
        $this->assertNotNull($response->headers->get('Cache-Control'));
        $this->assertNotNull($response->headers->get('Expires'));
    }

    public function testIncludeSubprocessTasks()
    {
        $mainRequest = ProcessRequest::factory()->create();
        $subprocessRequest1 = ProcessRequest::factory()->create([
            'parent_request_id' => $mainRequest->id,
        ]);
        $subprocessRequest2 = ProcessRequest::factory()->create([
            'parent_request_id' => $mainRequest->id,
        ]);
        $mainTask = ProcessRequestToken::factory()->create([
            'process_request_id' => $mainRequest->id,
        ]);
        $subTask1 = ProcessRequestToken::factory()->create([
            'process_request_id' => $subprocessRequest1->id,
        ]);
        $subTask2 = ProcessRequestToken::factory()->create([
            'process_request_id' => $subprocessRequest2->id,
        ]);

        $response = $this->apiCall('GET', route('api.1.1.tasks.index', [
            'process_request_id' => $mainRequest->id,
            'include_sub_tasks' => 1,
        ]));
        $tasks = $response->json()['data'];

        $this->assertEquals($mainTask->id, $tasks[0]['id']);
        $this->assertEquals($subTask1->id, $tasks[1]['id']);
        $this->assertEquals($subTask2->id, $tasks[2]['id']);
    }
}
