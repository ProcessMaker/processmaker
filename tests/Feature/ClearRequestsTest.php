<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Console\Commands\ProcessmakerClearRequests;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\BpmnDocument;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

class ClearRequestsTest extends TestCase
{
    use ResourceAssertionsTrait;
    use WithFaker;
    use RequestHelper;
    use ProcessTestingTrait;

    /**
     * @var Process
     */
    protected $process;

    private $requestStructure = [
        'id',
        'process_id',
        'user_id',
        'status',
        'name',
        'initiated_at',
        'created_at',
        'updated_at',
    ];

    const API_TEST_URL = '/comments';

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestProcess(array $data = [])
    {
        $data['bpmn'] = Process::getProcessTemplate('IntermediateTimerEvent.bpmn');
        $process = Process::factory()->create($data);

        return $process;
    }

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestCollaborationProcess()
    {
        $process = Process::factory()->create([
            'bpmn' => Process::getProcessTemplate('Collaboration.bpmn'),
        ]);
        //Assign the task to $this->user
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => '_5',
            'assignment_id' => $this->user->id,
            'assignment_type' => 'user',
        ]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => '_10',
            'assignment_id' => $this->user->id,
            'assignment_type' => 'user',
        ]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => '_24',
            'assignment_id' => $this->user->id,
            'assignment_type' => 'user',
        ]);

        return $process;
    }

    private function runCollaborationProcess()
    {
        $process = $this->createTestCollaborationProcess();
        //Start a process request
        $route = route('api.process_events.trigger', [$process->id, 'event' => '_4']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $tasks = $response->json('data');
        //Complete the task
        $route = route('api.tasks.update', [$tasks[0]['id'], 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $task = $response->json();
        //Get the list of tasks
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $tasks = $response->json('data');
        //Complete the task
        $index = $this->findTaskByName($tasks, 'Process Order');
        $route = route('api.tasks.update', [$tasks[$index]['id'], 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $task = $response->json();
        //Get the list of tasks
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $tasks = $response->json('data');
        //Complete the Final task
        $index = $this->findTaskByName($tasks, 'Finish');
        $route = route('api.tasks.update', [$tasks[$index]['id'], 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
    }

    /**
     * Get the index of a task by name.
     *
     * @param array $tasks
     * @param string $name
     *
     * @return int
     */
    private function findTaskByName(array $tasks, $name)
    {
        foreach ($tasks as $index => $task) {
            if ($task['element_name'] === $name) {
                break;
            }
        }

        return $index;
    }

    private function addSomeComments()
    {
        $response = $this->apiCall('GET', self::API_TEST_URL, $this->params());
        $response->assertStatus(200);

        $process = Process::factory()->create([
            'bpmn' => Process::getProcessTemplate('SingleTask.bpmn'),
        ]);
        $bpmnProcess = $process->getDefinitions()->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'process')->item(0);
        $bpmnProcessId = $bpmnProcess->getAttribute('id');

        // Add comments to Tokens
        $model = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => ProcessRequest::create([
                'name' => $this->faker->sentence(3),
                'data' => [],
                'status' => $this->faker->randomElement(['DRAFT', 'ACTIVE', 'COMPLETED']),
                'callable_id' => $bpmnProcessId,
                'user_id' => $this->user->id,
                'process_id' => $process->getKey(),
                'process_collaboration_id' => null,
            ])->id,
        ]);

        Comment::factory()->count(5)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => false,
        ]);

        Comment::factory()->count(5)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => true,
        ]);

        // Add comments to Requests
        $model = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'callable_id' => 'PROCESS_1',
            'process_collaboration_id' => null,
        ]);

        Comment::factory()->count(5)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => false,
        ]);

        Comment::factory()->count(5)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => true,
        ]);

        // Add 3 comments to Process
        $model = $process;
        Comment::factory()->count(3)->create([
            'commentable_id' => $model->getKey(),
            'commentable_type' => get_class($model),
            'hidden' => false,
        ]);
    }

    private function addUserMediaFiles()
    {
        // We create a fake file to upload
        Storage::fake('public');
        $fileUpload = UploadedFile::fake()->create('my_test_file123.txt', 1);

        // We create a model (in this case a user) and associate to him the file
        $model = User::factory()->create();
        $model->addMedia($fileUpload)->toMediaCollection('local');

        // Basic listing assertions
        $response = $this->apiCall('GET', self::API_TEST_URL, $this->params());

        // Validate the header status code
        $response->assertStatus(200);

        // Filtered listing assertions
        $response = $this->apiCall('GET', self::API_TEST_URL, $this->params('123'));
        $response->assertStatus(200);

        // Filtered listing assertions when filter string is not found
        $response = $this->apiCall('GET', self::API_TEST_URL, $this->params('xyz9393'));
        $response->assertStatus(200);
    }

    private function params($filter = null)
    {
        $params = [
            'commentable_id' => $this->user->getKey(),
            'commentable_type' => get_class($this->user),
        ];
        if ($filter) {
            $params['filter'] = $filter;
        }

        return $params;
    }

    private function addRequestMediaFiles()
    {
        // We create a fake file to upload
        Storage::fake('public');
        $fileUpload = UploadedFile::fake()->create('request_test_file456.txt', 1);

        // We create a model (in this case a user) and associate to him the file
        $model = ProcessRequest::orderBy('id', 'desc')->first();
        $model->addMedia($fileUpload)
            ->withCustomProperties(['data_name' => 'test'])
            ->toMediaCollection('local');

        // Basic listing assertions
        $response = $this->apiCall('GET', self::API_TEST_URL, $this->params());

        // Validate the header status code
        $response->assertStatus(200);

        // Filtered listing assertions
        $response = $this->apiCall('GET', self::API_TEST_URL, $this->params('456'));
        $response->assertStatus(200);

        // Filtered listing assertions when filter string is not found
        $response = $this->apiCall('GET', self::API_TEST_URL, $this->params('xyz9393'));
        $response->assertStatus(200);
    }

    private function runProcessWithTimers()
    {
        $this->be($this->user);
        $process = $this->createTestProcess();
        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('_2');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);

        // Complete first task to active the intermediate timer events.
        $token = $request->tokens()->where('element_id', '_3')->first();
        $this->completeTask($token);
    }

    public function testCommandClearRequests()
    {
        $existingProcessIds = Process::pluck('id');

        // Run process with timers
        $this->runProcessWithTimers();

        // Run a collaboration
        $this->runCollaborationProcess();

        // Add some media files
        $this->addUserMediaFiles();
        $this->addRequestMediaFiles();

        // Add some comments
        $this->addSomeComments();

        $this->assertEquals(4, ScheduledTask::count());
        $this->assertEquals(5, ProcessRequest::count());
        $this->assertEquals(1, ProcessCollaboration::count());
        $this->assertEquals(28, Comment::count());
        $this->assertEquals(2, Media::where('collection_name', 'local')->count());

        $this->artisan('processmaker:clear-requests')
            ->expectsQuestion(ProcessmakerClearRequests::message, 'yes')
            ->assertExitCode(0)
            ->run();

        $this->assertEquals(0, ProcessRequestToken::count());
        $this->assertEquals(0, ProcessRequest::count());
        $this->assertEquals(0, ProcessCollaboration::count());
        // 3 comments about Process should remain
        $this->assertEquals(3, Comment::count());
        $this->assertEquals(1, Media::where('collection_name', 'local')->count());

        // We need to do our own teardown here since were not using
        // transactions for this test
        $currentProcessIds = Process::pluck('id');
        $createdProcessIds = $currentProcessIds->diff($existingProcessIds);
        Process::destroy($createdProcessIds);
        User::where('username', '!=', '_pm4_anon_user')->forceDelete();
    }

    /**
     * Do not use transactions for this test
     */
    protected function connectionsToTransact()
    {
        return [];
    }
}
