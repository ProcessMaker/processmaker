<?php

namespace Tests\Feature\Api\V1_1;

use ProcessMaker\Http\Controllers\Api\V1_1\TaskController;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Models\Process;

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
}
