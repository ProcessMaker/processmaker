<?php

namespace Tests\Feature\Api;

use Faker\Provider\DateTime;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Jobs\ThrowSignalEvent;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Managers\WorkflowEventManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

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
            'bpmn' => file_get_contents(__DIR__ . '/processes/TriggerSignalInRequestTest.bpmn'),
        ]);

        // Start process from normal start event node_9
        $this->startProcess($process, 'node_9');
        WorkflowManager::throwSignalEvent('collection_1_update');
        $activeTokens = ProcessRequestToken::where('status', 'ACTIVE')->get();

        // Assertion: There should be two active tokens: the task next to the boundary and the task from the new request
        $this->assertCount(2, $activeTokens);
    }

    public function testBoundaryLoop()
    {
        $import = ImportProcess::dispatchNow(
            file_get_contents(base_path('tests/Fixtures/boundary_loop.json'))
        );
        $process = $import->process;
        $event = $process->getDefinitions()->getEvent('node_1');
        Passport::actingAs($this->user);
        $processRequest = WorkflowManager::triggerStartEvent($process, $event, [
            'array' => [1, 2],
            'user_id' => $this->user->id, ]
        );
        $formTasks = $processRequest->tokens->filter(function ($task) {
            return $task->element_name === 'Form Task';
        });
        $this->assertCount(2, $formTasks);

        WorkflowManager::completeTask($process, $processRequest, $formTasks->first(), []);
        $this->assertEquals('COMPLETED', $formTasks->first()->refresh()->status);
        $this->assertEquals('ACTIVE', $formTasks->last()->refresh()->status);

        WorkflowManager::throwSignalEvent('collection_1_update');
        $this->assertEquals('CLOSED', $formTasks->last()->refresh()->status);

        $lastTask = $processRequest->refresh()->tokens->last();
        $this->assertEquals('After Boundary', $lastTask->element_name);

        WorkflowManager::completeTask($process, $processRequest, $lastTask, []);
        $this->assertEquals('CLOSED', $lastTask->refresh()->status);

        $this->assertEquals('COMPLETED', $processRequest->refresh()->status);
    }
}
