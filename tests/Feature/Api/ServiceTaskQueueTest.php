<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Group;
use Illuminate\Support\Facades\Bus;
use ProcessMaker\Jobs\RefreshArtisanCaches;
use ProcessMaker\Jobs\RunServiceTask;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Test Service Task Queue
 */
#[Group('process_tests')]
final class ServiceTaskQueueTest extends TestCase
{
    use RequestHelper;
    use ProcessTestingTrait;

    /**
     * Tests the ServiceTask is dispatched to the custom queue.
     */
    public function testCustomQueue(): void
    {
        Bus::fake();

        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/ServiceTaskCustomQueue.bpmn'));
        $startEvent = 'node_1';

        // Start a process instance
        $this->startProcess($process, $startEvent);

        Bus::assertDispatched(RunServiceTask::class, function (RunServiceTask $job) {
            return $job->queue === 'custom-queue';
        });
    }

    /**
     * Tests the ServiceTask is dispatched to the bpmn queue.
     */
    public function testDefaultQueue(): void
    {
        Bus::fake();

        // Create a process
        $process = $this->createProcess(file_get_contents(__DIR__ . '/processes/ServiceTaskDefaultQueue.bpmn'));
        $startEvent = 'node_1';

        // Start a process instance
        $this->startProcess($process, $startEvent);

        Bus::assertDispatched(RunServiceTask::class, function (RunServiceTask $job) {
            return $job->queue === 'bpmn';
        });
    }
}
