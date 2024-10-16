<?php

namespace Tests\Feature\Api\V1_1;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Facades\ScreenCompiledManager;
use ProcessMaker\Http\Resources\V1_1\TaskScreen;
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
        $this->assertNull($response->headers->get('Expires'));
    }

    public function testShowScreenCache()
    {
        $content = file_get_contents(
            __DIR__ . '/Fixtures/nested_screen_process.json'
        );
        ImportProcess::dispatchSync($content);
        $request = ProcessRequest::factory()->create([
            'process_id' => Process::where('name', 'nested screen test')->first()->id,
        ]);
        $process = Process::where('name', 'nested screen test')->first();
        $processVersion = $process->getPublishedVersion([]);
        $task = ProcessRequestToken::factory()->create([
            'element_type' => 'task',
            'element_name' => 'Task 1',
            'element_id' => 'node_2',
            'process_id' => $process->id,
            'process_request_id' => $request->id,
        ]);
        $screenVersion = $task->getScreenVersion();

        // Prepare the key for the screen cache
        Auth::setUser($this->user);
        $processId = $process->id;
        $processVersionId = $processVersion->id;
        $language = $this->user->language;
        $screenId = $screenVersion->screen_id;
        $screenVersionId = $screenVersion->id;

        // Get the screen cache key
        $screenKey = ScreenCompiledManager::createKey(
            $processId,
            $processVersionId,
            $language,
            $screenId,
            $screenVersionId
        );

        // Prepare the screen content with nested to be stored in the cache
        $response = new TaskScreen($task);
        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(function () {
            return $this->user;
        });
        // add query param include=screen,nested
        $request->query->add(['include' => 'screen,nested']);
        $content = $response->toArray($request)['screen'];

        // Mock the ScreenCompiledManager
        ScreenCompiledManager::shouldReceive('createKey')
            ->once()
            ->withAnyArgs()
            ->andReturn($screenKey);
        ScreenCompiledManager::shouldReceive('getCompiledContent')
            ->once()
            ->with($screenKey)
            ->andReturn(null);
        ScreenCompiledManager::shouldReceive('storeCompiledContent')
            ->once()
            ->withAnyArgs($screenKey, $content)
            ->andReturn(null);

        // Assert the expected screen content is returned
        $response = $this->apiCall('GET', route('api.1.1.tasks.show.screen', $task->id) . '?include=screen,nested');
        $this->assertNotNull($response->json());
        $this->assertIsArray($response->json());
        $response->assertStatus(200);
        $response->assertJson($content);
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

    public function testResponseTaskWithTokenProperties()
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
        $response = $this->apiCall('GET', route('api.1.1.tasks.show', $task->id) . '?include=data');
        $this->assertNotNull($response->json());
        $this->assertIsArray($response->json());

        //Validate that field token_properties is sent with the response.
        $this->assertArrayHasKey('token_properties', $response->json());
    }
}
