<?php

namespace Tests\Feature\Metrics;

use ProcessMaker\Facades\Metrics;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\User;
use Prometheus\Counter;
use Tests\TestCase;

class TaskMetricsTest extends TestCase
{
    public function test_task_metric_was_stored()
    {
        $user = User::factory()->create([
            'is_administrator' => true,
        ]);

        $bpmnFile = 'tests/Fixtures/single_task_with_screen.bpmn';
        $process = $this->createProcessFromBPMN($bpmnFile, [
            'user_id' => $user->id,
        ]);

        $this->be($user);

        $startEvent = $process->getDefinitions()->getStartEvent('node_1');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);

        $formTask = $request->tokens()->where('element_id', 'node_2')->firstOrFail();

        // Complete the task
        WorkflowManager::completeTask($process, $request, $formTask, ['someValue' => 123]);

        // Verify that the metric was stored
        $this->assertMetricWasStored('activity_completed_total', [
            'activity_id' => 'node_2',
            'activity_name' => 'Form Task',
            'process_id' => $process->id,
            'request_id' => $request->id,
        ]);
    }

    private function assertMetricWasStored(string $name, array $labels)
    {
        $adapter = Metrics::getCollectionRegistry();
        $ns = config('app.prometheus_namespace', 'app');
        $metric = $adapter->getCounter($ns, $name);

        $this->assertInstanceOf(Counter::class, $metric);
        $labels = Metrics::addSystemLabels($labels);
        $this->assertEquals($metric->getLabelNames(), array_keys($labels));
    }
}
