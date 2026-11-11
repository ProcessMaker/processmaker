<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use ProcessMaker\Mcp\Processes\BpmnLayoutService;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BpmnLayoutServiceTest extends TestCase
{
    /**
     * @group requires-node
     */
    public function testLayoutPreservesPmAttributes(): void
    {
        $script = config('mcp.bpmn_layout.script_path');
        if (!is_string($script) || !is_file($script)) {
            $this->markTestSkipped('BPMN auto-layout script is not installed.');
        }

        $node = config('mcp.bpmn_layout.node_binary', 'node');
        $check = new Process([$node, '--version']);
        try {
            $check->mustRun();
        } catch (\Throwable) {
            $this->markTestSkipped('Node.js is not available for BPMN layout.');
        }

        $input = file_get_contents(base_path('tools/mcp/bpmn-auto-layout/fixtures/linear.bpmn'));
        $this->assertNotFalse($input);

        $result = (new BpmnLayoutService())->layout($input);

        $this->assertStringContainsString('pm:screenRef="1"', $result['bpmn']);
        $this->assertMatchesRegularExpression('/dc:Bounds x="\d+"/', $result['bpmn']);
    }
}
