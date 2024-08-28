<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class InterstitialTest extends TestCase
{
    use RequestHelper;
    use WithFaker;
    use HelperTrait;

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

    /**
     * Create a single task process assigned to $this->user
     */
    private function loadProcess($file)
    {
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate($file);
        return Process::factory()->create($data);
    }

    /**
     * @return Process[]
     */
    private function importProcessPackage(string $file)
    {
        $maxId = Process::max('id') ?? 0;
        $data = json_decode(file_get_contents(base_path('tests/Fixtures/' . $file)), true);
        $this->import($data);
        return Process::where('id', '>', $maxId)->get();
    }

    private function startProcess(Process $process, string $event, array $data = [])
    {
        $route = route('api.process_events.trigger', [$process->id, 'event' => $event]);
        $response = $this->apiCall('POST', $route, $data);
        return $response->json();
    }

    private function getActiveTasks()
    {
        //Get the active tasks of the request
        $route = route('api.tasks.index', ['status' => 'ACTIVE']);
        $response = $this->apiCall('GET', $route);
        return $response->json('data');
    }
    private function completeActiveTask(array $data = [])
    {
        //Get the active tasks of the request
        $tasks = $this->getActiveTasks();
        //Complete the task
        $route = route('api.tasks.update', [$tasks[0]['id'], 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        return $response->json();
    }

    private function broadcastedTo(RedirectToEvent $event, array $expectedChannels): bool
    {
        $channels = $event->broadcastOn();
        foreach ($channels as $channel) {
            if (!in_array($channel->name, $expectedChannels)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Execute a process and check the interstitial redirects
     */
    public function testExecuteProcessRedirects()
    {
        $processes = $this->importProcessPackage('task_subprocess_task.json');
        $process = $processes[0];
        //Start a process request
        Event::fake([RedirectToEvent::class]);
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $data = ['foo' => 'bar'];
        $response = $this->apiCall('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);

        // Check redirect to first task
        $dispatched = [];
        $tasks = $this->getActiveTasks();
        $expectedEvent = [
            'method' => 'redirectToTask',
            'params' => [
                [
                    'tokenId' => $tasks[0]['id'],
                    'nodeId' => $tasks[0]['element_id'],
                ]
            ],
        ];
        $dispatched[] = $expectedEvent;
        Event::assertDispatched(RedirectToEvent::class, function ($event) use ($expectedEvent) {
            return $event->method === $expectedEvent['method']
                && $event->params[0]['tokenId'] === $expectedEvent['params'][0]['tokenId']
                && $event->params[0]['nodeId'] === $expectedEvent['params'][0]['nodeId'];
        });

        // Complete active task (Task 1) and check RedirectToEvent dispatched
        $task = $this->completeActiveTask([]);
        $tasks = $this->getActiveTasks();
        $expectedEvent = [
            'method' => 'redirectToTask',
            'params' => [
                [
                    'tokenId' => $tasks[0]['id'],
                ]
            ],
        ];
        $dispatched[] = $expectedEvent;
        Event::assertDispatched(RedirectToEvent::class, function ($event) use ($expectedEvent) {
            return $event->method === $expectedEvent['method']
                && $event->params[0]['tokenId'] === $expectedEvent['params'][0]['tokenId'];
                
        });

        // Complete active task (sub process - Task 2) and check RedirectToEvent dispatched to next Task not to subprocess summary
        $task = $this->completeActiveTask([]);
        $tasks = $this->getActiveTasks();
        $expectedEvent = [
            'method' => 'redirectToTask',
            'params' => [
                [
                    'tokenId' => $tasks[0]['id'],
                ]
            ],
            'broadcastTo' => [
                'private-ProcessMaker.Models.ProcessRequest.' . $tasks[0]['process_request_id'], // active task: parent request
                'private-ProcessMaker.Models.ProcessRequest.' . $task['process_request_id'], // completed task: child request
            ]
        ];
        $dispatched[] = $expectedEvent;
        Event::assertDispatched(RedirectToEvent::class, function ($event) use ($expectedEvent, $task) {
            return $event->method === $expectedEvent['method']
                && $event->params[0]['tokenId'] === $expectedEvent['params'][0]['tokenId']
                && $this->broadcastedTo($event, $expectedEvent['broadcastTo']);
        });

        // Complete active task (Task 3) and check RedirectToEvent dispatched to parent process summary
        $task = $this->completeActiveTask([]);
        $tasks = $this->getActiveTasks();
        $expectedEvent = [
            'method' => 'processCompletedRedirect',
            'params' => [
                [],
                $task['user_id'],
                $task['process_request_id'],
            ],
        ];
        $dispatched[] = $expectedEvent;
        Event::assertDispatched(RedirectToEvent::class, function ($event) use ($expectedEvent) {
            return $event->method === $expectedEvent['method']
                && $event->params[1] === $expectedEvent['params'][1]
                && $event->params[2] === $expectedEvent['params'][2];
        });

        Event::assertDispatched(RedirectToEvent::class, count($dispatched));
    }
}
