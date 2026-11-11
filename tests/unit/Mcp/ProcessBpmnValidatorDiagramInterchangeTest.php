<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use ProcessMaker\Mcp\Processes\ProcessBpmnValidator;
use Tests\TestCase;

class ProcessBpmnValidatorDiagramInterchangeTest extends TestCase
{
    public function testMissingBpmnEdgeIsError(): void
    {
        $bpmn = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
  xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd"
  id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn:process id="Process_1" isExecutable="true">
    <bpmn:startEvent id="node_1"><bpmn:outgoing>flow_1</bpmn:outgoing></bpmn:startEvent>
    <bpmn:task id="node_2" pm:screenRef="1" pm:assignment="requester"><bpmn:incoming>flow_1</bpmn:incoming></bpmn:task>
    <bpmn:sequenceFlow id="flow_1" sourceRef="node_1" targetRef="node_2" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
      <bpmndi:BPMNShape id="shape_1" bpmnElement="node_1"><dc:Bounds x="10" y="10" width="36" height="36" /></bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="shape_2" bpmnElement="node_2"><dc:Bounds x="120" y="10" width="100" height="80" /></bpmndi:BPMNShape>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
XML;

        $validator = new ProcessBpmnValidator();

        $this->assertTrue($validator->lacksSequenceFlowDiagramInterchange($bpmn));

        $result = $validator->validate($bpmn);
        $this->assertFalse($result['ok']);
        $codes = array_column($result['errors'], 'code');
        $this->assertContains('missing_bpmn_edge', $codes);
    }

    public function testCompleteBpmnEdgePassesDiagramCheck(): void
    {
        $bpmn = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
  xmlns:di="http://www.omg.org/spec/DD/20100524/DI"
  xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd"
  id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn:process id="Process_1" isExecutable="true">
    <bpmn:startEvent id="node_1"><bpmn:outgoing>flow_1</bpmn:outgoing></bpmn:startEvent>
    <bpmn:task id="node_2" pm:screenRef="1" pm:assignment="requester"><bpmn:incoming>flow_1</bpmn:incoming></bpmn:task>
    <bpmn:sequenceFlow id="flow_1" sourceRef="node_1" targetRef="node_2" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
      <bpmndi:BPMNShape id="shape_1" bpmnElement="node_1"><dc:Bounds x="10" y="10" width="36" height="36" /></bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="shape_2" bpmnElement="node_2"><dc:Bounds x="120" y="10" width="100" height="80" /></bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="edge_1" bpmnElement="flow_1">
        <di:waypoint x="46" y="28" /><di:waypoint x="120" y="50" />
      </bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
XML;

        $validator = new ProcessBpmnValidator();

        $this->assertFalse($validator->lacksSequenceFlowDiagramInterchange($bpmn));

        $diagramErrors = array_filter(
            $validator->validate($bpmn)['errors'],
            static fn (array $row): bool => in_array($row['code'], ['missing_bpmn_edge', 'incomplete_bpmn_edge'], true),
        );
        $this->assertSame([], $diagramErrors);
    }
}
