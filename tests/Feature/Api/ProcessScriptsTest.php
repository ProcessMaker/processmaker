<?php
namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class ProcessScriptsTest extends TestCase
{

    use ResourceAssertionsTrait;
    use WithFaker;
    use RequestHelper;

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
    protected function withUserSetup()
    {
        $this->process = $this->createTestProcess();
    }

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestProcess(array $data = [])
    {
        $data['bpmn'] = Process::getProcessTemplate('ScriptTasks.bpmn');
        $process = factory(Process::class)->create($data);
        return $process;
    }

    /**
     * Execute a process
     */
    public function testExecuteAProcess()
    {
        if (!file_exists(config('app.bpm_scripts_home')) || !file_exists(config('app.bpm_scripts_docker'))) {
            $this->markTestSkipped(
                'This test requires docker'
            );
        }
        //Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => '_2']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        //Verify status
        $this->assertStatus(201, $response);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();
        //Get the active tasks of the request
        $route = route('api.tasks.index');
        $response = $this->apiCall('GET', $route);
        $tasks = $response->json('data');
        //Check that two script tasks were completed.
        $this->assertArraySubset([
            [
                'element_type' => 'scriptTask',
                'status' => 'CLOSED',
            ],
            [
                'element_type' => 'scriptTask',
                'status' => 'CLOSED',
            ]], $tasks);
        //Get process instance
        $processInstance = ProcessRequest::where('id', $tasks[0]['process_request_id'])->firstOrFail();
        //Check the data
        $this->assertArrayHasKey('random', $processInstance->data);
        $this->assertArrayHasKey('double', $processInstance->data);
        $this->assertInternalType('int', $processInstance->data['random']);
        $this->assertInternalType('int', $processInstance->data['double']);
        $this->assertEquals(2 * $processInstance->data['random'], $processInstance->data['double']);
    }
}
