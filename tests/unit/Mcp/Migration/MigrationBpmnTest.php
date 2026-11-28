<?php

namespace Tests\Unit\Mcp\Migration;

use ProcessMaker\Mcp\Migration\BpmnDiagramInterchange;
use ProcessMaker\Mcp\Migration\MigrationBpmn;
use Tests\TestCase;

class MigrationBpmnTest extends TestCase
{
    public function testSanitizeXmlIdsRemapsNumericPm3ElementIds(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL">
  <bpmn:process id="ProcessId" isExecutable="true">
    <bpmn:startEvent id="4831240716a357b8d408417073978576">
      <bpmn:outgoing>2466207176a357b8d420422029986737</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:task id="4154827546a357b8d3ce9d1035298340" name="Task 1">
      <bpmn:incoming>2466207176a357b8d420422029986737</bpmn:incoming>
      <bpmn:outgoing>7614540406aa1b70c1739a6064656012</bpmn:outgoing>
    </bpmn:task>
    <bpmn:sequenceFlow id="2466207176a357b8d420422029986737"
      sourceRef="4831240716a357b8d408417073978576"
      targetRef="4154827546a357b8d3ce9d1035298340"/>
  </bpmn:process>
</bpmn:definitions>
XML;

        $result = MigrationBpmn::sanitizeXmlIds($xml);

        $this->assertNotSame([], $result['id_map']);
        $this->assertStringNotContainsString('4831240716a357b8d408417073978576', $result['xml']);
        $this->assertStringContainsString('sourceRef="node_', $result['xml']);
        $this->assertStringContainsString('<bpmn:incoming>node_', $result['xml']);
    }

    public function testRemapBundleElementIdsUpdatesTaskUids(): void
    {
        $bundle = MigrationBpmn::remapBundleElementIds([
            'tasks' => [['tas_uid' => '4154827546a357b8d3ce9d1035298340']],
            'task_element_map' => [[
                'tas_uid' => '4154827546a357b8d3ce9d1035298340',
                'element_id' => '4154827546a357b8d3ce9d1035298340',
            ]],
        ], [
            '4154827546a357b8d3ce9d1035298340' => 'node_task1',
        ]);

        $this->assertSame('node_task1', $bundle['tasks'][0]['tas_uid']);
        $this->assertSame('node_task1', $bundle['task_element_map'][0]['element_id']);
    }

    public function testRemapBundleElementIdsUpdatesStepTaskUids(): void
    {
        $bundle = MigrationBpmn::remapBundleElementIds([
            'steps' => [[
                'step_type' => 'DYNAFORM',
                'tas_uid' => '4154827546a357b8d3ce9d1035298340',
                'dyn_uid' => '9089870096aa1b700247d05097963765',
            ]],
        ], [
            '4154827546a357b8d3ce9d1035298340' => 'node_task1',
        ]);

        $this->assertSame('node_task1', $bundle['steps'][0]['tas_uid']);
    }

    public function testRemapBundleElementIdsBuildsTaskElementMapFromTasksWhenMissing(): void
    {
        $bundle = MigrationBpmn::remapBundleElementIds([
            'tasks' => [['tas_uid' => '4154827546a357b8d3ce9d1035298340']],
        ], [
            '4154827546a357b8d3ce9d1035298340' => 'node_task1',
        ]);

        $this->assertSame('node_task1', $bundle['task_element_map'][0]['tas_uid']);
        $this->assertSame('node_task1', $bundle['task_element_map'][0]['element_id']);
    }

    public function testRebuildBpmnDiagramReplacesStaleLayout(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
  xmlns:di="http://www.omg.org/spec/DD/20100524/DI">
  <bpmn:process id="ProcessId" isExecutable="true">
    <bpmn:startEvent id="start">
      <bpmn:outgoing>flow_1</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:userTask id="task_1" name="Task 1">
      <bpmn:incoming>flow_1</bpmn:incoming>
      <bpmn:outgoing>flow_2</bpmn:outgoing>
    </bpmn:userTask>
    <bpmn:userTask id="task_2" name="Task 2">
      <bpmn:incoming>flow_2</bpmn:incoming>
      <bpmn:outgoing>flow_3</bpmn:outgoing>
    </bpmn:userTask>
    <bpmn:endEvent id="end">
      <bpmn:incoming>flow_3</bpmn:incoming>
    </bpmn:endEvent>
    <bpmn:sequenceFlow id="flow_1" sourceRef="start" targetRef="task_1"/>
    <bpmn:sequenceFlow id="flow_2" sourceRef="task_1" targetRef="task_2"/>
    <bpmn:sequenceFlow id="flow_3" sourceRef="task_2" targetRef="end"/>
  </bpmn:process>
  <bpmndi:BPMNDiagram id="old_diagram">
    <bpmndi:BPMNPlane bpmnElement="ProcessId">
      <bpmndi:BPMNShape id="old_start" bpmnElement="start">
        <dc:Bounds x="10" y="10" width="36" height="36"/>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="old_flow" bpmnElement="flow_1">
        <di:waypoint x="46" y="28"/>
        <di:waypoint x="200" y="28"/>
      </bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
XML;

        $result = BpmnDiagramInterchange::rebuild($xml);

        $this->assertStringContainsString('BPMNShape_task_1', $result);
        $this->assertStringContainsString('BPMNShape_task_2', $result);
        $this->assertStringContainsString('BPMNEdge_flow_2', $result);
        $this->assertStringContainsString('BPMNEdge_flow_3', $result);
        $this->assertStringNotContainsString('old_diagram', $result);
        $this->assertMatchesRegularExpression(
            '/BPMNShape_start[^>]+>.*?x="120".*?BPMNShape_task_1[^>]+>.*?x="300"/s',
            $result,
        );
    }

    public function testEnsureBpmnDiagramAddsDiagramInterchange(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL">
  <bpmn:process id="ProcessId" isExecutable="true">
    <bpmn:startEvent id="start">
      <bpmn:outgoing>flow_1</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:task id="task_1" name="Task 1">
      <bpmn:incoming>flow_1</bpmn:incoming>
    </bpmn:task>
    <bpmn:sequenceFlow id="flow_1" sourceRef="start" targetRef="task_1"/>
  </bpmn:process>
</bpmn:definitions>
XML;

        $result = BpmnDiagramInterchange::ensure($xml);

        $this->assertStringContainsString('bpmndi:BPMNDiagram', $result);
        $this->assertStringContainsString('BPMNShape_start', $result);
        $this->assertStringContainsString('BPMNEdge_flow_1', $result);
    }

    public function testSanitizeXmlIdsRemapsDiagramBpmnElementReferences(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI">
  <bpmn:process id="ProcessId" isExecutable="true">
    <bpmn:startEvent id="4831240716a357b8d408417073978576"/>
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_ProcessId">
    <bpmndi:BPMNPlane bpmnElement="ProcessId">
      <bpmndi:BPMNShape id="BPMNShape_old" bpmnElement="4831240716a357b8d408417073978576"/>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
XML;

        $result = MigrationBpmn::sanitizeXmlIds($xml);

        $this->assertStringContainsString('bpmnElement="node_', $result['xml']);
        $this->assertStringNotContainsString('4831240716a357b8d408417073978576', $result['xml']);
    }
}
