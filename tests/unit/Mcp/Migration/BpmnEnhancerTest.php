<?php

namespace Tests\unit\Mcp\Migration;

use ProcessMaker\Mcp\Migration\BpmnEnhancer;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\WorkflowServiceProvider;
use Tests\TestCase;

class BpmnEnhancerTest extends TestCase
{
    public function testApplyCreatesBoundaryTimerAndCallActivity(): void
    {
        $user = User::factory()->create();
        $child = Process::factory()->create(['user_id' => $user->id]);
        $bpmn = file_get_contents(base_path('tests/Fixtures/single_task_with_screen.bpmn'));
        $process = Process::factory()->create([
            'user_id' => $user->id,
            'bpmn' => $bpmn,
        ]);

        $result = (new BpmnEnhancer())->apply($process, [
            'task_element_map' => [[
                'tas_uid' => 'pm3-task-uid',
                'element_id' => 'node_2',
            ]],
            'timer_events' => [[
                'act_uid' => 'pm3-task-uid',
                'hour' => 2,
            ]],
            'sub_processes' => [[
                'tas_uid' => 'pm3-task-uid',
                'pro_uid' => 'child-pro-uid',
            ]],
            'subprocess_map' => [
                'child-pro-uid' => (string) $child->id,
            ],
        ]);

        $process->refresh();
        $xpath = new \DOMXPath($process->getDefinitions(true));
        $xpath->registerNamespace('bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL');
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);

        $this->assertCount(1, $result['applied_timers']);
        $this->assertCount(1, $result['applied_sub_processes']);
        $this->assertSame(1, $xpath->query("//bpmn:boundaryEvent[@attachedToRef='node_2']/bpmn:timerEventDefinition")->length);
        $this->assertSame(1, $xpath->query("//bpmn:callActivity[@id='node_2']")->length);
    }
}
