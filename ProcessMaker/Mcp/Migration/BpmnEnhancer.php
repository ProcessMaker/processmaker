<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Providers\WorkflowServiceProvider;

class BpmnEnhancer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     applied_timers: array<int, mixed>,
     *     applied_sub_processes: array<int, mixed>,
     *     warnings: array<int, string>
     * }
     */
    public function apply(Process $process, array $payload): array
    {
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);
        $taskElementMap = MigrationBpmn::buildTaskElementMap($payload, $xpath);
        $subprocessMap = is_array($payload['subprocess_map'] ?? null) ? $payload['subprocess_map'] : [];

        $appliedTimers = [];
        $appliedSubProcesses = [];
        $warnings = [];
        $modified = false;
        $counter = 1;

        foreach (array_merge($payload['timer_events'] ?? [], $payload['case_schedulers'] ?? []) as $timer) {
            if (!is_array($timer)) {
                continue;
            }

            $taskUid = $timer['act_uid'] ?? $timer['tas_uid'] ?? null;
            if (!is_string($taskUid) || $taskUid === '') {
                continue;
            }

            $elementId = $taskElementMap[$taskUid] ?? null;
            if ($elementId === null) {
                $warnings[] = 'Timer on PM3 task ' . $taskUid . ' has no BPMN element mapping.';
                continue;
            }

            if ($this->hasBoundaryTimer($xpath, $elementId)) {
                continue;
            }

            $duration = $this->buildIsoDuration($timer);
            $boundaryId = 'migration_timer_' . $counter;
            $endId = 'migration_timer_end_' . $counter;
            $flowId = 'migration_timer_flow_' . $counter;
            $counter++;

            $this->appendBoundaryTimer($definitions, $elementId, $boundaryId, $endId, $flowId, $duration);
            $appliedTimers[] = [
                'task_uid' => $taskUid,
                'element_id' => $elementId,
                'duration' => $duration,
                'boundary_id' => $boundaryId,
            ];
            $modified = true;
        }

        foreach ($payload['sub_processes'] ?? [] as $subProcess) {
            if (!is_array($subProcess)) {
                continue;
            }

            $tasUid = $subProcess['tas_uid'] ?? null;
            $pm3ProcessUid = $subProcess['pro_uid'] ?? null;
            if (!is_string($tasUid) || $tasUid === '' || !is_string($pm3ProcessUid) || $pm3ProcessUid === '') {
                continue;
            }

            $pm4ProcessId = $subprocessMap[$pm3ProcessUid] ?? null;
            if ($pm4ProcessId === null || $pm4ProcessId === '') {
                $warnings[] = 'Sub-process ' . $pm3ProcessUid . ' requires manual subprocess_map entry.';
                continue;
            }

            $elementId = $taskElementMap[$tasUid] ?? null;
            if ($elementId === null) {
                $warnings[] = 'Sub-process task ' . $tasUid . ' has no BPMN element mapping.';
                continue;
            }

            $taskNode = $xpath->query("//*[@id='{$elementId}']")->item(0);
            if (!($taskNode instanceof DOMElement)) {
                $warnings[] = 'BPMN element ' . $elementId . ' not found for sub-process task ' . $tasUid . '.';
                continue;
            }

            if (in_array($taskNode->localName, ['callActivity'], true)) {
                continue;
            }

            $this->replaceWithCallActivity(
                $definitions,
                $taskNode,
                (string) $pm4ProcessId,
                (string) ($payload['subprocess_start_event_map'][$pm3ProcessUid] ?? 'node_2')
            );

            $appliedSubProcesses[] = [
                'tas_uid' => $tasUid,
                'pm3_process_uid' => $pm3ProcessUid,
                'pm4_process_id' => (string) $pm4ProcessId,
                'element_id' => $elementId,
            ];
            $modified = true;
        }

        if ($modified) {
            $process->bpmn = $definitions->saveXML();
            $process->saveOrFail();
        }

        return [
            'applied_timers' => $appliedTimers,
            'applied_sub_processes' => $appliedSubProcesses,
            'warnings' => $warnings,
        ];
    }

    private function hasBoundaryTimer(DOMXPath $xpath, string $elementId): bool
    {
        return $xpath->query("//bpmn:boundaryEvent[@attachedToRef='{$elementId}']/bpmn:timerEventDefinition")->length > 0;
    }

    /**
     * @param  array<string, mixed>  $timer
     */
    private function buildIsoDuration(array $timer): string
    {
        $days = (int) ($timer['day'] ?? 0);
        $hours = (int) ($timer['hour'] ?? 0);
        $minutes = (int) ($timer['minute'] ?? 0);

        if ($days === 0 && $hours === 0 && $minutes === 0) {
            foreach (['configuration_data', 'config'] as $key) {
                $raw = $timer[$key] ?? null;
                if (!is_string($raw) || trim($raw) === '') {
                    continue;
                }

                $parsed = json_decode($raw, true);
                if (!is_array($parsed)) {
                    continue;
                }

                $days = (int) ($parsed['days'] ?? $parsed['day'] ?? $days);
                $hours = (int) ($parsed['hours'] ?? $parsed['hour'] ?? $hours);
                $minutes = (int) ($parsed['minutes'] ?? $parsed['minute'] ?? $minutes);
                break;
            }
        }

        if ($days === 0 && $hours === 0 && $minutes === 0) {
            $option = strtoupper((string) ($timer['option'] ?? ''));
            if ($option === 'HOURLY') {
                $hours = 1;
            } elseif ($option === 'DAILY') {
                $days = 1;
            } else {
                return 'PT1H';
            }
        }

        $duration = 'P';
        if ($days > 0) {
            $duration .= $days . 'D';
        }

        $duration .= 'T';
        if ($hours > 0) {
            $duration .= $hours . 'H';
        }
        if ($minutes > 0) {
            $duration .= $minutes . 'M';
        }

        if (str_ends_with($duration, 'T')) {
            $duration .= '0S';
        }

        return $duration;
    }

    private function appendBoundaryTimer(
        DOMDocument $definitions,
        string $attachedToRef,
        string $boundaryId,
        string $endId,
        string $flowId,
        string $duration
    ): void {
        $processNode = $definitions->getElementsByTagNameNS(MigrationBpmn::BPMN_NS, 'process')->item(0);
        if (!($processNode instanceof DOMElement)) {
            return;
        }

        $boundary = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'bpmn:boundaryEvent');
        $boundary->setAttribute('id', $boundaryId);
        $boundary->setAttribute('attachedToRef', $attachedToRef);
        $boundary->setAttribute('cancelActivity', 'true');

        $timerDefinition = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'bpmn:timerEventDefinition');
        $timeDuration = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'bpmn:timeDuration', $duration);
        $timerDefinition->appendChild($timeDuration);
        $boundary->appendChild($timerDefinition);

        $outgoing = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'bpmn:outgoing', $flowId);
        $boundary->appendChild($outgoing);

        $endEvent = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'bpmn:endEvent');
        $endEvent->setAttribute('id', $endId);
        $endEvent->setAttribute('name', 'Timer Expired');

        $incoming = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'bpmn:incoming', $flowId);
        $endEvent->appendChild($incoming);

        $flow = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'bpmn:sequenceFlow');
        $flow->setAttribute('id', $flowId);
        $flow->setAttribute('sourceRef', $boundaryId);
        $flow->setAttribute('targetRef', $endId);

        $processNode->appendChild($boundary);
        $processNode->appendChild($endEvent);
        $processNode->appendChild($flow);
    }

    private function replaceWithCallActivity(
        DOMDocument $definitions,
        DOMElement $taskNode,
        string $pm4ProcessId,
        string $startEventId
    ): void {
        $callActivity = $definitions->createElementNS(MigrationBpmn::BPMN_NS, 'bpmn:callActivity');
        $callActivity->setAttribute('id', $taskNode->getAttribute('id'));
        $callActivity->setAttribute('name', $taskNode->getAttribute('name') ?: 'Sub Process');

        $calledElement = 'ProcessId-' . $pm4ProcessId;
        $callActivity->setAttribute('calledElement', $calledElement);
        $callActivity->setAttributeNS(
            WorkflowServiceProvider::PROCESS_MAKER_NS,
            'pm:config',
            json_encode([
                'calledElement' => $calledElement,
                'processId' => (int) $pm4ProcessId,
                'startEvent' => $startEventId,
                'name' => $callActivity->getAttribute('name'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
        );

        foreach ($taskNode->childNodes as $child) {
            if ($child instanceof DOMElement && in_array($child->localName, ['incoming', 'outgoing'], true)) {
                $callActivity->appendChild($child->cloneNode(true));
            }
        }

        $taskNode->parentNode?->replaceChild($callActivity, $taskNode);
    }
}
