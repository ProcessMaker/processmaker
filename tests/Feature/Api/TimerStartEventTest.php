<?php
namespace Tests\Feature\Api;

use Faker\Provider\DateTime;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Managers\WorkflowEventManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

/**
 * Test the process execution with requests
 *
 * @group process_tests
 */
class TimerStartEventTest extends TestCase
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
        $data['bpmn'] = Process::getProcessTemplate('TimerStartEvent.bpmn');
        $process = factory(Process::class)->create($data);
        return $process;
    }

    /**
     * Execute a process
     */
    public function ExecuteAProcess()
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

        //Get the closed tasks of the request
        $tasks = ProcessRequestToken::where('process_id', '=', $this->process->id)
            ->where('element_type', '=', 'scriptTask')
            ->where('status', '=', 'CLOSED')
            ->get();
        $this->assertEquals(count($tasks), 2);

        //Get process instance
        $processInstance = ProcessRequest::where('id', $tasks[0]['process_request_id'])->firstOrFail();
        //Check the data
        $this->assertArrayHasKey('random', $processInstance->data);
        $this->assertArrayHasKey('double', $processInstance->data);
        $this->assertInternalType('int', $processInstance->data['random']);
        $this->assertInternalType('int', $processInstance->data['double']);
        $this->assertEquals(2 * $processInstance->data['random'], $processInstance->data['double']);
    }

    public function executeTimerStartEvent()
    {
        $this->process->getDefinitions();
    }

    public function testNextDateNayraInterval()
    {
        $manager = new WorkflowEventManager();


        $cases = [
            [
                'currentDate' => '2019-02-11T00:00:00Z',
                'interval' => 'R4/2019-02-15T00:00:00Z/P1M',
                'expectedNextDate' => '2019-03-15T00:00:00Z'
            ],
            [
                'currentDate' => '2019-05-11T00:00:00Z',
                'interval' => 'R4/2019-02-15T00:00:00Z/P1M',
                'expectedNextDate' => '2019-05-15T00:00:00Z'
            ],
            [
                'currentDate' => '2019-05-11T00:00:00Z',
                'interval' => 'R/2019-02-15T00:00:00Z/P1M',
                'expectedNextDate' => '2019-05-15T00:00:00Z'
            ],
//            [
//                'currentDate' => '2019-05-11T00:00:00Z',
//                'interval' => 'R/2019-02-19T00:00-04:00/P1W',
//                'expectedNextDate' => '2019-05-15T00:00:00Z'
//            ],
        ];

        foreach($cases as $case) {
            $currentDate = new \DateTime($case['currentDate']);
            $nayraInterval = $case['interval'];
            $expectedNextDate = new \DateTime($case['expectedNextDate']);
            $nextDate = $manager->nextDate($currentDate, $nayraInterval);
            $this->assertEquals($expectedNextDate, $nextDate);
        }
    }
}
