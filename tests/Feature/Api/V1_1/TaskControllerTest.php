<?php

namespace Tests\Feature\Api\V1_1;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Cache\Screens\ScreenCacheManager;
use ProcessMaker\Facades\ScreenCompiledManager;
use ProcessMaker\Http\Resources\V1_1\TaskScreen;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenVersion;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RequestHelper;

    protected function tearDown(): void
    {
        parent::tearDown();
        ScreenCacheFactory::setTestInstance(null);
    }

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
        $process = Process::where('name', 'nested screen test')->first();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);
        $processVersion = $process->getPublishedVersion([]);
        $task = ProcessRequestToken::factory()->create([
            'element_type' => 'task',
            'element_name' => 'Task 1',
            'element_id' => 'node_2',
            'process_id' => $process->id,
            'process_request_id' => $request->id,
        ]);
        $screenVersion = $task->getScreenVersion();
        $this->assertNotNull($screenVersion, 'Screen version not found');

        // Set up test user
        Auth::setUser($this->user);

        // Create cache manager mock
        $screenCache = $this->getMockBuilder(ScreenCacheManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up ScreenCacheFactory to return our mock
        ScreenCacheFactory::setTestInstance($screenCache);

        // Set up expected cache key parameters
        $expectedParams = [
            'process_id' => (int) $process->id,
            'process_version_id' => (int) $processVersion->id,
            'language' => $this->user->language,
            'screen_id' => (int) $screenVersion->screen_id,
            'screen_version_id' => (int) $screenVersion->id,
        ];

        // Mock createKey method with array parameter
        $screenCache->expects($this->once())
            ->method('createKey')
            ->with($expectedParams)
            ->willReturn("pid_{$process->id}_{$processVersion->id}_{$this->user->language}_sid_{$screenVersion->screen_id}_{$screenVersion->id}");

        $response = $this->apiCall('GET', route('api.1.1.tasks.show.screen', $task->id) . '?include=screen,nested');
        $this->assertNotNull($response->json());
        $this->assertIsArray($response->json());
        $this->assertNotNull($response->headers->get('Cache-Control'));
        $this->assertNull($response->headers->get('Expires'));
    }

    public function testShowScreenCache()
    {
        // Create test data
        $content = file_get_contents(
            __DIR__ . '/Fixtures/nested_screen_process.json'
        );
        ImportProcess::dispatchSync($content);

        $process = Process::where('name', 'nested screen test')->first();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);

        $processVersion = $process->getPublishedVersion([]);
        $task = ProcessRequestToken::factory()->create([
            'element_type' => 'task',
            'element_name' => 'Task 1',
            'element_id' => 'node_2',
            'process_id' => $process->id,
            'process_request_id' => $request->id,
        ]);

        $screenVersion = $task->getScreenVersion();
        $this->assertNotNull($screenVersion, 'Screen version not found');

        // Set up test user
        Auth::setUser($this->user);

        // Create cache manager mock
        $screenCache = $this->getMockBuilder(ScreenCacheManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up ScreenCacheFactory to return our mock
        ScreenCacheFactory::setTestInstance($screenCache);

        // Set up expected cache key parameters
        $expectedParams = [
            'process_id' => (int) $process->id,
            'process_version_id' => (int) $processVersion->id,
            'language' => $this->user->language,
            'screen_id' => (int) $screenVersion->screen_id,
            'screen_version_id' => (int) $screenVersion->id,
        ];

        $screenKey = "pid_{$process->id}_{$processVersion->id}_{$this->user->language}_sid_{$screenVersion->screen_id}_{$screenVersion->id}";

        // Mock createKey method
        $screenCache->expects($this->once())
            ->method('createKey')
            ->with($expectedParams)
            ->willReturn($screenKey);

        // Mock cached content
        $cachedContent = [
            'id' => $screenVersion->screen_id,
            'screen_version_id' => $screenVersion->id,
            'config' => ['some' => 'config'],
            'watchers' => [],
            'computed' => [],
            'type' => 'FORM',
            'title' => 'Test Screen',
            'description' => '',
            'screen_category_id' => null,
            'nested' => [],
        ];

        $screenCache->expects($this->once())
            ->method('get')
            ->with($screenKey)
            ->willReturn($cachedContent);

        // Make the API call
        $response = $this->apiCall(
            'GET',
            route('api.1.1.tasks.show.screen', $task->id) . '?include=screen,nested'
        );

        // Assertions
        $this->assertNotNull($response->json());
        $this->assertIsArray($response->json());
        $response->assertStatus(200);
        $response->assertJson($cachedContent);
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
