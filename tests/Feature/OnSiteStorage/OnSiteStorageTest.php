<?php

namespace Tests\Feature\OnSiteStorage;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Exception\ReferentialIntegrityException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class OnSiteStorageTest extends TestCase
{
    use RequestHelper;
    use WithFaker;

    /**
     * @var Process $process
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
        'updated_at'
    ];

    /**
     * Initialize the controller tests
     *
     */
    protected function withUserSetUp()
    {
        $this->process = $this->createTestProcess();
    }

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestProcess(array $data = [])
    {
        $data['bpmn'] = Process::getProcessTemplate('SingleTask.bpmn');
        $process = factory(Process::class)->create($data);
        //Assign the task to $this->user
        $taskId = 'UserTaskUID';
        factory(ProcessTaskAssignment::class)->create([
            'process_id' => $process->id,
            'process_task_id' => $taskId,
            'assignment_id' => $this->user->id,
            'assignment_type' => User::class,
        ]);
        return $process;
    }

    /**
     * Verifies that the data is stored in the database
     */
    public function testDataIsStoredInExternalConnection()
    {
        if (!config('database.enable_external_connection')) {
            $this->markTestSkipped('ENABLE_EXTERNAL_CONNECTION is not enabled');
        }
        //Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'StartEventUID']);
        $data = ['testField' => 'stored value'];
        $response = $this->apiCall('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();

        //Get the active tasks of the request and complete the task
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);
        $tasks = $response->json('data');

        //Complete the task
        $route = route('api.tasks.update', [$tasks[0]['id'], 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $task = $response->json();

        //Check the task is closed
        $this->assertEquals('CLOSED', $task['status']);
        $this->assertNotNull($task['completed_at']);

        //Check the request is completed
        $this->assertEquals('COMPLETED', $task['process_request']['status']);
        $this->assertNotNull($task['process_request']['completed_at']);

        // Assert that the data was stored in the correct connection
        $storedRequest = DB::connection('data')->table('process_requests')->get()->first();
        $this->assertEquals($data, (array)json_decode($storedRequest->data));
    }


    /**
     * Tests if restrictions are applied when deleting a request
     */
    public function testDeleteProcessRequestOnCascade()
    {
        if (!config('database.enable_external_connection')) {
            $this->markTestSkipped('ENABLE_EXTERNAL_CONNECTION is not enabled');
        }

        // create a request with tokens
        $request = factory(ProcessRequest::class, 1)->create()->first();
        $requestToken = factory(ProcessRequestToken::class, 1)
                        ->create([
                                    'process_request_id' => $request->id,
                                    'process_id' => $request->process_id
                                ])
                        ->first();

        // A process can be deleted if it has requests
        $this->expectException(ReferentialIntegrityException::class);
        Process::destroy($requestToken->process_id);
    }
}
