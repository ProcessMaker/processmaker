<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Process;
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
     * Verifies that migrations create the core tables that can be moved to and external database
     */
    public function testMigrations()
    {
        // Drop the tables created in the migrations
        Schema::connection('data')->dropIfExists('comments');
        Schema::connection('data')->dropIfExists('process_requests');

        // Run the migrations that create the tables
        Artisan::call('migrate:refresh',
                    array('--path' => 'database/migrations/2019_01_14_201209_create_comments_table.php',
                            '--force' => true));

        Artisan::call('migrate:refresh',
            array('--path' => 'database/migrations/2018_09_07_174154_create_process_requests_table.php',
                '--force' => true));

        // Assert that the migrations created the tables
        $this->assertEquals(True, Schema::connection('data')->hasTable('comments'));
        $this->assertEquals(True, Schema::connection('data')->hasTable('process_requests'));
    }
}
