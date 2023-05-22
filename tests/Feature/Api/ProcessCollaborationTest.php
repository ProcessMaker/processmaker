<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class ProcessCollaborationTest extends TestCase
{
    use WithFaker;
    use RequestHelper;

    /**
     * @var User
     */
    protected $user;

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

    /**
     * Execute a process
     */
    public function testExecuteACollaboration()
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
        $task = $response->json();
        //Get the list of tasks
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $tasks = $response->json('data');

        $this->assertEquals('CLOSED', $tasks[0]['status']);
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
}
