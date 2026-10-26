<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Mcp\Migration\BpmnDiagramInterchange;
use ProcessMaker\Mcp\Migration\MigrationBpmn;
use ProcessMaker\Mcp\Migration\Writer;
use ProcessMaker\Models\Process;
use ProcessMaker\Providers\WorkflowServiceProvider;

class BpmnDesigner
{
    public function __construct(private readonly Writer $writer)
    {
    }

    /**
     * @return array{process_id: string, element_id: string, name: string, flows: array<int, string>}
     */
    public function addUserTask(
        string $processId,
        string $name,
        ?string $elementId = null,
        ?string $insertAfterElementId = null,
    ): array {
        $process = Process::findOrFail($processId);
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);
        $processNode = $xpath->query('//bpmn:process')->item(0);

        if (!($processNode instanceof DOMElement)) {
            throw ValidationException::withMessages(['bpmn' => ['No process element found in BPMN.']]);
        }

        $taskId = $elementId ?? 'node_' . Str::random(8);
        if ($xpath->query("//*[@id='{$taskId}']")->item(0) !== null) {
            throw ValidationException::withMessages(['element_id' => ["Element ID already exists: {$taskId}"]]);
        }

        $task = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'userTask');
        $task->setAttribute('id', $taskId);
        $task->setAttribute('name', $name);
        $processNode->appendChild($task);

        $flowIds = [];

        if ($insertAfterElementId !== null) {
            $flowIds = $this->insertTaskAfterElement($definitions, $xpath, $processNode, $task, $insertAfterElementId);
        } else {
            $flowIds = $this->wireTaskBeforeEnd($definitions, $xpath, $processNode, $task);
        }

        $this->writer->updateProcessBpmn($processId, $this->prepareBpmnXml($definitions));

        return [
            'process_id' => $processId,
            'element_id' => $taskId,
            'name' => $name,
            'flows' => $flowIds,
        ];
    }

    /**
     * @return array{process_id: string, flow_id: string, source_ref: string, target_ref: string}
     */
    public function addSequenceFlow(
        string $processId,
        string $sourceRef,
        string $targetRef,
        ?string $flowId = null,
        ?string $condition = null,
    ): array {
        $process = Process::findOrFail($processId);
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);
        $processNode = $xpath->query('//bpmn:process')->item(0);

        if (!($processNode instanceof DOMElement)) {
            throw ValidationException::withMessages(['bpmn' => ['No process element found in BPMN.']]);
        }

        if ($xpath->query("//*[@id='{$sourceRef}']")->item(0) === null) {
            throw ValidationException::withMessages(['source_ref' => ["Source element not found: {$sourceRef}"]]);
        }
        if ($xpath->query("//*[@id='{$targetRef}']")->item(0) === null) {
            throw ValidationException::withMessages(['target_ref' => ["Target element not found: {$targetRef}"]]);
        }

        $flowId = $flowId ?? 'flow_' . Str::random(8);
        $flow = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'sequenceFlow');
        $flow->setAttribute('id', $flowId);
        $flow->setAttribute('sourceRef', $sourceRef);
        $flow->setAttribute('targetRef', $targetRef);

        if ($condition !== null && $condition !== '') {
            $flow->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'conditionalFlow', 'true');
            $flow->setAttribute('name', $condition);
        }

        $processNode->appendChild($flow);
        $source = $xpath->query("//*[@id='{$sourceRef}']")->item(0);
        $target = $xpath->query("//*[@id='{$targetRef}']")->item(0);
        if ($source instanceof DOMElement) {
            $this->appendChildRef($source, 'outgoing', $flowId);
        }
        if ($target instanceof DOMElement) {
            $this->appendChildRef($target, 'incoming', $flowId);
        }

        $this->writer->updateProcessBpmn($processId, $this->prepareBpmnXml($definitions));

        return [
            'process_id' => $processId,
            'flow_id' => $flowId,
            'source_ref' => $sourceRef,
            'target_ref' => $targetRef,
        ];
    }

    /**
     * @return array{process_id: string, element_id: string, name: string}
     */
    public function addExclusiveGateway(
        string $processId,
        string $name,
        ?string $elementId = null,
    ): array {
        $process = Process::findOrFail($processId);
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);
        $processNode = $xpath->query('//bpmn:process')->item(0);

        if (!($processNode instanceof DOMElement)) {
            throw ValidationException::withMessages(['bpmn' => ['No process element found in BPMN.']]);
        }

        $gatewayId = $elementId ?? 'gateway_' . Str::random(8);
        $gateway = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'exclusiveGateway');
        $gateway->setAttribute('id', $gatewayId);
        $gateway->setAttribute('name', $name);
        $processNode->appendChild($gateway);

        $this->writer->updateProcessBpmn($processId, $this->prepareBpmnXml($definitions));

        return [
            'process_id' => $processId,
            'element_id' => $gatewayId,
            'name' => $name,
        ];
    }

    private function prepareBpmnXml(DOMDocument $definitions): string
    {
        return BpmnDiagramInterchange::rebuild($definitions->saveXML() ?: '');
    }

    /**
     * Insert a new task on the flow that currently reaches the end event.
     *
     * @return array<int, string>
     */
    private function wireTaskBeforeEnd(
        DOMDocument $definitions,
        \DOMXPath $xpath,
        DOMElement $processNode,
        DOMElement $task,
    ): array {
        $end = $xpath->query('//bpmn:endEvent')->item(0);
        if (!($end instanceof DOMElement)) {
            throw ValidationException::withMessages([
                'bpmn' => ['No end event found. Set bpmn_template to SingleTask or provide insert_after_element_id.'],
            ]);
        }

        $endId = $end->getAttribute('id');
        $incomingFlow = $xpath->query("//bpmn:sequenceFlow[@targetRef='{$endId}']")->item(0);
        if (!($incomingFlow instanceof DOMElement)) {
            throw ValidationException::withMessages([
                'bpmn' => ['No sequence flow reaches the end event.'],
            ]);
        }

        $flowId = $incomingFlow->getAttribute('id');
        $taskId = $task->getAttribute('id');

        $incomingFlow->setAttribute('targetRef', $taskId);
        $this->removeIncomingReference($xpath, $endId, $flowId);
        $this->appendChildRef($task, 'incoming', $flowId);

        $newFlowId = 'flow_' . Str::random(8);
        $newFlow = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'sequenceFlow');
        $newFlow->setAttribute('id', $newFlowId);
        $newFlow->setAttribute('sourceRef', $taskId);
        $newFlow->setAttribute('targetRef', $endId);
        $processNode->appendChild($newFlow);

        $this->appendChildRef($task, 'outgoing', $newFlowId);
        $this->appendChildRef($end, 'incoming', $newFlowId);

        return [$flowId, $newFlowId];
    }

    /**
     * @return array<int, string>
     */
    private function insertTaskAfterElement(
        DOMDocument $definitions,
        \DOMXPath $xpath,
        DOMElement $processNode,
        DOMElement $task,
        string $afterElementId,
    ): array {
        $outgoingFlow = $xpath->query("//bpmn:sequenceFlow[@sourceRef='{$afterElementId}']")->item(0);
        if (!($outgoingFlow instanceof DOMElement)) {
            throw ValidationException::withMessages([
                'insert_after_element_id' => ["No outgoing flow from element: {$afterElementId}"],
            ]);
        }

        $oldTarget = $outgoingFlow->getAttribute('targetRef');
        $taskId = $task->getAttribute('id');

        $flow1Id = $outgoingFlow->getAttribute('id');
        $outgoingFlow->setAttribute('targetRef', $taskId);
        $this->removeIncomingReference($xpath, $oldTarget, $flow1Id);
        $this->appendChildRef($task, 'incoming', $flow1Id);

        $flow2 = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'sequenceFlow');
        $flow2Id = 'flow_' . Str::random(8);
        $flow2->setAttribute('id', $flow2Id);
        $flow2->setAttribute('sourceRef', $taskId);
        $flow2->setAttribute('targetRef', $oldTarget);
        $processNode->appendChild($flow2);

        $this->appendChildRef($task, 'outgoing', $flow2Id);
        $targetNode = $xpath->query("//*[@id='{$oldTarget}']")->item(0);
        if ($targetNode instanceof DOMElement) {
            $this->appendChildRef($targetNode, 'incoming', $flow2Id);
        }

        return [$flow1Id, $flow2Id];
    }

    private function appendChildRef(DOMElement $node, string $tag, string $flowId): void
    {
        $ref = $node->ownerDocument->createElementNS(MigrationBpmn::BPMN_NS, $tag);
        $ref->textContent = $flowId;
        $node->appendChild($ref);
    }

    private function removeIncomingReference(\DOMXPath $xpath, string $elementId, string $flowId): void
    {
        $node = $xpath->query("//*[@id='{$elementId}']")->item(0);
        if (!($node instanceof DOMElement)) {
            return;
        }

        foreach ($xpath->query('.//bpmn:incoming', $node) as $incoming) {
            if ($incoming->textContent === $flowId) {
                $node->removeChild($incoming);

                return;
            }
        }
    }
}
