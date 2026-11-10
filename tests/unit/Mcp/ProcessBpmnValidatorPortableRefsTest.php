<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use ProcessMaker\Mcp\Processes\ProcessBpmnValidator;
use Tests\TestCase;

class ProcessBpmnValidatorPortableRefsTest extends TestCase
{
    public function testUnresolvedScreenRefIsError(): void
    {
        $bpmn = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
  xmlns:di="http://www.omg.org/spec/DD/20100524/DI"
  xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd"
  id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn" exporter="ProcessMaker Modeler">
  <bpmn:process id="Process_1" name="portable-ref-test" isExecutable="true">
    <bpmn:startEvent id="node_1" name="Start">
      <bpmn:outgoing>node_4</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:task id="node_2" name="Form Task" pm:screenRef="${screen:my-form}" pm:assignment="requester">
      <bpmn:incoming>node_4</bpmn:incoming>
      <bpmn:outgoing>node_5</bpmn:outgoing>
    </bpmn:task>
    <bpmn:endEvent id="node_3" name="End">
      <bpmn:incoming>node_5</bpmn:incoming>
    </bpmn:endEvent>
    <bpmn:sequenceFlow id="node_4" sourceRef="node_1" targetRef="node_2" />
    <bpmn:sequenceFlow id="node_5" sourceRef="node_2" targetRef="node_3" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
      <bpmndi:BPMNShape id="shape_1" bpmnElement="node_1"><dc:Bounds x="100" y="100" width="36" height="36" /></bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="shape_2" bpmnElement="node_2"><dc:Bounds x="180" y="78" width="100" height="80" /></bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="shape_3" bpmnElement="node_3"><dc:Bounds x="330" y="100" width="36" height="36" /></bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="edge_4" bpmnElement="node_4"><di:waypoint x="136" y="118" /><di:waypoint x="180" y="118" /></bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="edge_5" bpmnElement="node_5"><di:waypoint x="280" y="118" /><di:waypoint x="330" y="118" /></bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
XML;

        $result = (new ProcessBpmnValidator())->validate($bpmn);

        $this->assertFalse($result['ok']);
        $codes = array_column($result['errors'], 'code');
        $this->assertContains('unresolved_screen_ref', $codes);
    }

    public function testNumericScreenRefPassesPortableCheck(): void
    {
        $bpmn = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
  xmlns:di="http://www.omg.org/spec/DD/20100524/DI"
  xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd"
  id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn" exporter="ProcessMaker Modeler">
  <bpmn:process id="Process_1" name="numeric-ref-test" isExecutable="true">
    <bpmn:startEvent id="node_1" name="Start">
      <bpmn:outgoing>node_4</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:task id="node_2" name="Form Task" pm:screenRef="25" pm:assignment="requester">
      <bpmn:incoming>node_4</bpmn:incoming>
      <bpmn:outgoing>node_5</bpmn:outgoing>
    </bpmn:task>
    <bpmn:endEvent id="node_3" name="End">
      <bpmn:incoming>node_5</bpmn:incoming>
    </bpmn:endEvent>
    <bpmn:sequenceFlow id="node_4" sourceRef="node_1" targetRef="node_2" />
    <bpmn:sequenceFlow id="node_5" sourceRef="node_2" targetRef="node_3" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
      <bpmndi:BPMNShape id="shape_1" bpmnElement="node_1"><dc:Bounds x="100" y="100" width="36" height="36" /></bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="shape_2" bpmnElement="node_2"><dc:Bounds x="180" y="78" width="100" height="80" /></bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="shape_3" bpmnElement="node_3"><dc:Bounds x="330" y="100" width="36" height="36" /></bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="edge_4" bpmnElement="node_4"><di:waypoint x="136" y="118" /><di:waypoint x="180" y="118" /></bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="edge_5" bpmnElement="node_5"><di:waypoint x="280" y="118" /><di:waypoint x="330" y="118" /></bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
XML;

        $result = (new ProcessBpmnValidator())->validate($bpmn);
        $codes = array_column($result['errors'], 'code');
        $this->assertNotContains('unresolved_screen_ref', $codes);
    }
}
