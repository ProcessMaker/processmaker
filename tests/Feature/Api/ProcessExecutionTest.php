<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
class ProcessExecutionTest extends TestCase
{

    use DatabaseTransactions;
    use WithFaker;

    /**
     *
     * @var User $user 
     */
    protected $user;

    /**
     * @var Process $process
     */
    protected $process;
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
        $this->process = $this->createTestProcess();
    }

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestProcess()
    {
        $process = factory(Process::class)->create([
            'bpmn' => Process::getProcessTemplate('SingleTask.bpmn')
        ]);
        //Assign the task to $this->user
        $taskUuid = 'UserTaskUID';
        factory(ProcessTaskAssignment::class)->create([
            'process_uuid' => $process->uuid,
            'process_task_uuid' => $taskUuid,
            'assignment_uuid' => $this->user->uuid,
            'assignment_type' => 'user',
        ]);
        return $process;
    }

    /**
     * Execute a process
     */
    public function testExecuteAProcess()
    {
        //Start a process request
        $route = route('process_event', [$this->process->uuid_text, 'StartEventUID']);
        $data = [];
        $response = $this->json('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('tasks.index');
        $response = $this->json('GET', $route);
        $tasks = $response->json('data');
        //Complete the task
        $route = route('tasks.update', [$tasks[0]['uuid'], 'status' => 'COMPLETED']);
        $response = $this->json('PUT', $route, $data);
        $task = $response->json();
        //Check the task is closed
        $this->assertEquals('CLOSED', $task['status']);
        $this->assertNotNull($task['completed_at']);
        //Check the request is completed
        $this->assertEquals('COMPLETED', $task['process_request']['status']);
        $this->assertNotNull($task['process_request']['completed_at']);
    }
}
