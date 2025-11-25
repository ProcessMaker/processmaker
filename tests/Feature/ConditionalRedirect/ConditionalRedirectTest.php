<?php

namespace Tests\Feature\ConditionalRedirect;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class ConditionalRedirectTest extends TestCase
{
    use RequestHelper;
    use WithFaker;

    /**
     * @var Process
     */
    protected $process;

    /**
     * Initialize the controller tests
     */
    protected function withUserSetup()
    {
        $this->process = $this->createTestProcess();
    }

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestProcess()
    {
        return $this->createProcessFromBPMN('tests/Feature/ImportExport/fixtures/conditional_redirect.xml');
    }

    /**
     * Execute a process with a conditional redirect task source
     */
    public function testConditionalRedirectTaskSource()
    {
        $this->conditionalRedirectTest(
            requestData: [
                'var_1' => true,
            ],
            expectedElementDestination: [
                'type' => 'taskSource',
                'value' => null,
            ]
        );
    }

    public function testConditionalRedirectTaskList()
    {
        $this->conditionalRedirectTest(
            requestData: [
                'var_2' => true,
            ],
            expectedElementDestination: [
                'type' => 'taskList',
                'value' => route('tasks.index'),
            ]
        );
    }

    public function testConditionalRedirectProcessLaunchpad()
    {
        $this->conditionalRedirectTest(
            requestData: [
                'var_3' => true,
            ],
            expectedElementDestination: [
                'type' => 'processLaunchpad',
                'value' => route('process.browser.index', [
                    'process' => $this->process->id,
                    'categorySelected' => -1,
                ]),
            ]
        );
    }

    public function testConditionalRedirectHomepageDashboard()
    {
        $this->conditionalRedirectTest(
            requestData: [
                'var_4' => true,
            ],
            expectedElementDestination: [
                'type' => 'homepageDashboard',
                'value' => route('home'),
            ]
        );
    }

    public function testConditionalRedirectCustomDashboard()
    {
        $this->conditionalRedirectTest(
            requestData: [
                'var_5' => true,
            ],
            expectedElementDestination: [
                'type' => 'customDashboard',
                // url from process configuration
                'value' => 'http://processmaker.test/home/customize-ui/dashboards/y5QwQRuwhjN4uzprckahYtZ2JHYlpxVNnMR2mbfkQhYVWF4dSPvKRISxiG1v6ZAP',
            ]
        );
    }

    public function testConditionalRedirectExternalURL()
    {
        $this->conditionalRedirectTest(
            requestData: [
                'var_6' => true,
            ],
            expectedElementDestination: [
                'type' => 'externalURL',
                'value' => 'https://github.com',
            ]
        );
    }

    public function testConditionalRedirectDisplayNextAssignedTask()
    {
        $this->conditionalRedirectTest(
            requestData: [
                'var_7' => true,
            ],
            expectedElementDestination: [
                'type' => 'displayNextAssignedTask',
                'value' => null,
            ]
        );
    }

    public function testConditionalRedirectDefault()
    {
        $this->conditionalRedirectTest(
            requestData: [],
            expectedElementDestination: [
                'type' => 'displayNextAssignedTask',
                'value' => null,
            ],
        );
    }

    private function conditionalRedirectTest(array $requestData, array $expectedElementDestination)
    {
        Event::fake([
            RedirectToEvent::class,
        ]);

        // Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'node_23']);

        $response = $this->apiCall('POST', $route, $requestData);
        // Verify status
        $response->assertStatus(201);

        // Get the active tasks of the request
        $tasks = $this->getActiveTasks();

        // Check Interstitial redirect to first task
        $dispatched = [];
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

        // Verify the Element Destination for the Active Task
        $currentTask = $this->getTaskById($tasks[0]['id']);
        $this->assertEquals($expectedElementDestination, $currentTask['elementDestination']);

        //Complete the task
        $route = route('api.tasks.update', [$tasks[0]['id'], 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $requestData]);
        $task = $response->json();
        //Check the task is closed
        $this->assertEquals('CLOSED', $task['status']);
        $this->assertNotNull($task['completed_at']);

        // Check RedirectToEvent dispatched
        //$tasks = $this->getActiveTasks();
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
    }

    private function getActiveTasks()
    {
        //Get the active tasks of the request
        $route = route('api.tasks.index', ['status' => 'ACTIVE']);
        $response = $this->apiCall('GET', $route);
        return $response->json('data');
    }

    private function getTaskById($id)
    {
        $route = route('api.tasks.show', [$id, 'include' => 'data,elementDestination']);
        $response = $this->apiCall('GET', $route);
        return $response->json();
    }
}
