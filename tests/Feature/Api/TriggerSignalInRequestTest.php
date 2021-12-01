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
 * Test the process execution with requests
 *
 * @group process_tests
 * @group timer_events
 */
class TriggerSignalInRequestTest extends TestCase
{
    use RequestHelper;
    use ProcessTestingTrait;

    public function testScheduleStartEvent()
    {
        $process = $this->createProcess([
            'id' => 1,
            'bpmn' => file_get_contents(__DIR__ . '/processes/TriggerSignalInRequestTest.bpmn')
        ]);

        // Start process from normal start event node_9
        $instance = $this->startProcess($process, 'node_9');
        WorkflowManager::throwSignalEvent('collection_1_update');
        $activeTokens = ProcessRequestToken::where('status', 'ACTIVE')->get();
        $this->assertCount(2, $activeTokens);
    }
}
