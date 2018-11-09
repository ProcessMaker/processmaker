<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Script;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Test the process execution with service task
 *
 * @group process_tests
 */
class ServiceTaskExecutionTest extends TestCase
{

    use RequestHelper;
    use WithFaker;

    const START_EVENT_ID = '_3';

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
        factory(Script::class)->create([
            'key' => 'EchoConnector',
            'language' => 'php',
            'code' => '<?php return ["pong" => $data["ping"]];',
        ]);
        $this->process = $this->createTestProcess();
    }

    /**
     * Create a single task process assigned to $this->user
     */
    private function createTestProcess(array $data = [])
    {
        $data['bpmn'] = Process::getProcessTemplate('ServiceTaskProcess.bpmn');
        $process = factory(Process::class)->create($data);
        return $process;
    }

    /**
     * Execute a process with service task
     */
    public function testExecuteAProcess()
    {
        //Start a process request
        $route = route('api.process_events.trigger',
            [$this->process->id, 'event' => self::START_EVENT_ID]);
        $ping = '1';
        $data = [
            'ping' => $ping,
        ];
        $response = $this->apiCall('POST', $route, $data);
        //Verify status
        $response->assertStatus(201);
        //Verify the structure
        $response->assertJsonStructure($this->requestStructure);
        $request = $response->json();

        $requestId = $request['id'];

        $request = ProcessRequest::find($requestId);

        //Assertion: If the service task is executed it will return a pong
        $this->assertEquals($request->data['pong'], $ping);
    }
}
