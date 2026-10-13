<?php

namespace Tests\Feature\Api;

use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * FOUR-6787: an Intermediate Message Throw Event with several incoming
 * sequence flows must not wait for a token on every incoming flow.
 *
 * Fixture: tests/Fixtures/four_6787_intermediate_throw_event.json (process 219).
 * Topology: Form Task -> Exclusive Gateway (select = 1 / 2 / 3)
 *   select 1 -> Form Task 1 -> node_19 (Intermediate Message Throw)
 *   select 2 -> Form Task 2 -> node_19
 *   select 3 -> Form Task 3 -> node_19
 *   node_19 -> Message End Event (node_23)
 */
class IntermediateThrowEventMergeTest extends TestCase
{
    use RequestHelper;

    /**
     * Import the fixture, start a request and complete the first Form Task with the given select value.
     *
     * @return array{process: Process, instance: ProcessRequest}
     */
    private function startAndSelectBranch(string $select): array
    {
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../Fixtures/four_6787_intermediate_throw_event.json')
        );

        $process = Process::orderBy('id', 'desc')->first();

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $this->apiCall('POST', $route, []);

        $instance = ProcessRequest::orderBy('id', 'desc')->first();
        $startTask = ProcessRequestToken::where('element_name', 'Form Task')
            ->where('process_request_id', $instance->id)
            ->first();
        WorkflowManager::completeTask($process, $instance, $startTask, ['select' => $select]);

        return ['process' => $process, 'instance' => $instance];
    }

    /**
     * Complete the branch task (Form Task 1, 2 or 3) that follows the exclusive gateway.
     */
    private function completeBranchTask(Process $process, ProcessRequest $instance, string $branchTaskName): void
    {
        $task = ProcessRequestToken::where('element_name', $branchTaskName)
            ->where('process_request_id', $instance->id)
            ->first();
        $this->assertNotNull($task, "Expected branch task '{$branchTaskName}' to be created");
        WorkflowManager::completeTask($process, $instance, $task, []);
    }

    public function testBranchOneCompletesRequest()
    {
        ['process' => $process, 'instance' => $instance] = $this->startAndSelectBranch('1');
        $this->completeBranchTask($process, $instance, 'Form Task 1');

        $instance->refresh();
        $this->assertEquals('COMPLETED', $instance->status);
    }

    public function testBranchTwoCompletesRequest()
    {
        ['process' => $process, 'instance' => $instance] = $this->startAndSelectBranch('2');
        $this->completeBranchTask($process, $instance, 'Form Task 2');

        $instance->refresh();
        $this->assertEquals('COMPLETED', $instance->status);
    }

    public function testBranchThreeCompletesRequest()
    {
        ['process' => $process, 'instance' => $instance] = $this->startAndSelectBranch('3');
        $this->completeBranchTask($process, $instance, 'Form Task 3');

        $instance->refresh();
        $this->assertEquals('COMPLETED', $instance->status);
    }
}
