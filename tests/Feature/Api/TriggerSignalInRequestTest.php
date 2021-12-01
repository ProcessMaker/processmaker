<?php
namespace Tests\Feature\Api;

use Faker\Provider\DateTime;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Managers\WorkflowEventManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

/**
 * Tests boundary signal events in Jobs triggers the right number of requests
 *
 * @group process_tests
 */
class TriggerSignalInRequestTest extends TestCase
{
    use RequestHelper;
    use ProcessTestingTrait;

    /**
     * Tests one process with boundary signal event and one with start signal event.
     * 
     **/
    public function testScheduleStartEvent()
    {
        $process = $this->createProcess([
            'id' => 1,
            'bpmn' => file_get_contents(__DIR__ . '/processes/TriggerSignalInRequestTest.bpmn')
        ]);

        // Start process from normal start event node_9
        $this->startProcess($process, 'node_9');
        WorkflowManager::throwSignalEvent('collection_1_update');
        $activeTokens = ProcessRequestToken::where('status', 'ACTIVE')->get();

        // Assertion: There should be two active tokens: the task next to the boundary and the task from the new request
        $this->assertCount(2, $activeTokens);
    }
}
