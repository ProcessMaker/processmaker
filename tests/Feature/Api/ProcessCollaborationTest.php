<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\TestCase;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class ProcessCollaborationTest extends TestCase
{

    use WithFaker;

    /**
     *
     * @var User $user 
     */
    protected $user;

    private $requestStructure = [
        'uuid',
        'process_uuid',
        'user_uuid',
        'status',
        'name',
        'initiated_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Initialize the controller tests
     *
     */
    protected function setUp()
    {
        parent::setUp();
        //Login as an valid user
        $this->user = factory(User::class)->create();
        $this->actingAs($this->user, 'api');
    }

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestCollaborationProcess()
    {
        $process = factory(Process::class)->create([
            'bpmn' => Process::getProcessTemplate('Collaboration.bpmn')
        ]);
        //Assign the task to $this->user
        factory(ProcessTaskAssignment::class)->create([
            'process_uuid' => $process->uuid,
            'process_task_uuid' => '_5',
            'assignment_uuid' => $this->user->uuid,
            'assignment_type' => 'user',
        ]);
        factory(ProcessTaskAssignment::class)->create([
            'process_uuid' => $process->uuid,
            'process_task_uuid' => '_10',
            'assignment_uuid' => $this->user->uuid,
            'assignment_type' => 'user',
        ]);
        factory(ProcessTaskAssignment::class)->create([
            'process_uuid' => $process->uuid,
            'process_task_uuid' => '_24',
            'assignment_uuid' => $this->user->uuid,
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
        $route = route('api.process_events.trigger', [$process->uuid_text, 'event' => '_4']);
        $data = [];
        $response = $this->json('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Complete the task
        $route = route('api.tasks.update', [$tasks[0]['uuid'], 'status' => 'COMPLETED']);
        $response = $this->json('PUT', $route, $data);
        $task = $response->json();
        //Get the list of tasks
        $route = route('api.tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Complete the task
        $index = $this->findTaskByName($tasks, 'Process Order');
        $route = route('api.tasks.update', [$tasks[$index]['uuid'], 'status' => 'COMPLETED']);
        $response = $this->json('PUT', $route, $data);
        $task = $response->json();
        //Get the list of tasks
        $route = route('api.tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Complete the Final task
        $index = $this->findTaskByName($tasks, 'Finish');
        $route = route('api.tasks.update', [$tasks[$index]['uuid'], 'status' => 'COMPLETED']);
        $response = $this->json('PUT', $route, $data);
        $task = $response->json();
        //Get the list of tasks
        $route = route('api.tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        $this->assertEquals('CLOSED', $tasks[0]['status']);
        $this->assertEquals('CLOSED', $tasks[1]['status']);
        $this->assertEquals('CLOSED', $tasks[2]['status']);
        $this->assertEquals('CLOSED', $tasks[3]['status']);
        $this->assertEquals('CLOSED', $tasks[4]['status']);
    }

    /**
     * Get the index of a task by name.
     *
     * @param array $tasks
     * @param string $name
     *
     * @return integer
     */
    private function findTaskByName(array $tasks, $name)
    {
        foreach($tasks as $index => $task) {
            if ($task['element_name']===$name) {
                break;
            }
        }
        return $index;
    }
}
