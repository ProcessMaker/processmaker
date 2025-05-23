<?php

namespace Tests\Model;

use DOMXPath;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use stdClass;
use Tests\TestCase;

class ProcessRequestTokenTest extends TestCase
{
    public function testSetStagePropertiesInRecord()
    {
        // Create a partial mock of the token
        $token = $this->getMockBuilder(ProcessRequestToken::class)
            ->onlyMethods(['getInstance'])
            ->getMock();

        // Fake BPMN XML with a sequenceFlow and pm:config
        $bpmnXml = <<<'XML'
                    <bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:pm="http://processmaker.com/BPMN/Configuration">
                        <bpmn:sequenceFlow id="flow_1" targetRef="element_123" pm:config='{"stage": {"id": 7, "name": "Review"}}'/>
                    </bpmn:definitions>
                    XML;

        // Simulate getInstance returning a process with BPMN XML
        $instance = new stdClass();
        $instance->process = new stdClass();
        $instance->process->bpmn = $bpmnXml;

        $token->method('getInstance')->willReturn($instance);
        $token->element_id = 'element_123';

        // Act
        $token->setStagePropertiesInRecord();

        // Assert
        $this->assertEquals(7, $token->stage_id);
        $this->assertEquals('Review', $token->stage_name);
    }
}
