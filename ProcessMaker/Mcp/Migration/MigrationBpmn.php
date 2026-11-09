<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

use DOMDocument;
use DOMElement;
use DOMXPath;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Providers\WorkflowServiceProvider;
use ProcessMaker\Rules\BPMNValidation;

final class MigrationBpmn
{
    public const BPMN_NS = 'http://www.omg.org/spec/BPMN/20100524/MODEL';

    public static function createXPath(DOMDocument $definitions): DOMXPath
    {
        $xpath = new DOMXPath($definitions);
        $xpath->registerNamespace('bpmn', self::BPMN_NS);
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);

        return $xpath;
    }

    /**
     * PM3 exports numeric element UIDs as BPMN ids; PM4 XSD requires NCName (letter/_ first).
     *
     * @return array{xml: string, id_map: array<string, string>, warnings: array<int, string>}
     */
    public static function sanitizeXmlIds(string $xml): array
    {
        $document = new DOMDocument();
        if (@$document->loadXML($xml) === false) {
            return [
                'xml' => $xml,
                'id_map' => [],
                'warnings' => ['Unable to parse BPMN XML for ID sanitization.'],
            ];
        }

        $xpath = self::createXPath($document);
        $idMap = [];
        $counter = 1;

        foreach ($xpath->query('//*[@id]') as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            $oldId = $node->getAttribute('id');
            if ($oldId === '' || self::isValidBpmnId($oldId)) {
                continue;
            }

            $idMap[$oldId] = self::makeValidBpmnId($oldId, $counter++);
        }

        if ($idMap === []) {
            self::normalizeFlowNodeChildOrder($xpath);

            return [
                'xml' => self::ensureDiagramInterchange($document->saveXML() ?: $xml),
                'id_map' => [],
                'warnings' => [],
            ];
        }

        self::remapAttributeValues($xpath, 'id', $idMap);
        self::remapAttributeValues($xpath, 'sourceRef', $idMap);
        self::remapAttributeValues($xpath, 'targetRef', $idMap);
        self::remapAttributeValues($xpath, 'attachedToRef', $idMap);
        self::remapAttributeValues($xpath, 'bpmnElement', $idMap);
        self::remapFlowReferenceElements($xpath, $idMap);
        self::normalizeFlowNodeChildOrder($xpath);

        $xml = $document->saveXML() ?: $xml;

        return [
            'xml' => self::ensureDiagramInterchange($xml),
            'id_map' => $idMap,
            'warnings' => ['Remapped ' . count($idMap) . ' PM3 BPMN element IDs to PM4-compatible values.'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function validate(string $bpmn): array
    {
        $schemaErrors = [];
        $document = new BpmnDocument();

        try {
            $document->loadXML($bpmn);
        } catch (\ErrorException $e) {
            return [$e->getMessage()];
        }

        try {
            $document->validateBPMNSchema(public_path('definitions/ProcessMaker.xsd'));
        } catch (\Exception $e) {
            $schemaErrors = $document->getValidationErrors();
            $schemaErrors[] = $e->getMessage();
        }

        $diagrams = $document->getElementsByTagNameNS(
            'http://www.omg.org/spec/BPMN/20100524/DI',
            'BPMNDiagram'
        );
        if ($diagrams->length > 1) {
            $schemaErrors[] = 'Multiple diagrams are not supported';
        }

        $rulesValidation = new BPMNValidation();
        if (!$rulesValidation->passes('document', $document)) {
            $schemaErrors[] = [
                'title' => 'BPMN Validation failed',
                'text' => 'Some bpmn elements do not comply with the validation',
                'errors' => $rulesValidation->errors('document', $document)->getMessages(),
            ];
        }

        return $schemaErrors;
    }

    /**
     * PM3 MCP export includes BPMNDiagram; only synthesize layout when it is missing.
     */
    private static function ensureDiagramInterchange(string $xml): string
    {
        if (stripos($xml, 'BPMNDiagram') !== false) {
            return $xml;
        }

        return BpmnDiagramInterchange::ensure($xml);
    }

    /**
     * @param  array<string, string>  $idMap
     * @return array<string, mixed>
     */
    public static function remapBundleElementIds(array $bundle, array $idMap): array
    {
        if ($idMap === []) {
            return $bundle;
        }

        $remap = static fn (?string $value): ?string => ($value !== null && $value !== '' && isset($idMap[$value]))
            ? $idMap[$value]
            : $value;

        foreach ($bundle['task_element_map'] ?? [] as $index => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $bundle['task_element_map'][$index]['element_id'] = $remap((string) ($entry['element_id'] ?? ''));
            if (isset($entry['tas_uid'], $idMap[$entry['tas_uid']])) {
                $bundle['task_element_map'][$index]['tas_uid'] = $idMap[$entry['tas_uid']];
            }
        }

        foreach ($bundle['tasks'] ?? [] as $index => $task) {
            if (!is_array($task)) {
                continue;
            }

            $tasUid = (string) ($task['tas_uid'] ?? '');
            if ($tasUid !== '' && isset($idMap[$tasUid])) {
                $bundle['tasks'][$index]['tas_uid'] = $idMap[$tasUid];
            }
        }

        foreach ($bundle['task_assignments'] ?? [] as $index => $assignment) {
            if (!is_array($assignment)) {
                continue;
            }

            $tasUid = (string) ($assignment['tas_uid'] ?? '');
            if ($tasUid !== '' && isset($idMap[$tasUid])) {
                $bundle['task_assignments'][$index]['tas_uid'] = $idMap[$tasUid];
            }
        }

        foreach (['steps', 'document_steps', 'routes', 'gateways', 'web_entries', 'abe_configurations', 'schedulers', 'sub_processes'] as $section) {
            foreach ($bundle[$section] ?? [] as $index => $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $bundle[$section][$index] = self::remapEntryUidFields($entry, $idMap);
            }
        }

        foreach ($bundle['script_tasks'] ?? [] as $index => $scriptTask) {
            if (!is_array($scriptTask)) {
                continue;
            }

            $bundle['script_tasks'][$index] = self::remapEntryUidFields($scriptTask, $idMap, ['act_uid', 'tas_uid']);
        }

        if (($bundle['task_element_map'] ?? []) === [] && ($bundle['tasks'] ?? []) !== []) {
            foreach ($bundle['tasks'] as $task) {
                if (!is_array($task)) {
                    continue;
                }

                $tasUid = (string) ($task['tas_uid'] ?? '');
                if ($tasUid !== '') {
                    $bundle['task_element_map'][] = [
                        'tas_uid' => $tasUid,
                        'element_id' => $tasUid,
                    ];
                }
            }
        }

        return $bundle;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, string>  $idMap
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private static function remapEntryUidFields(array $entry, array $idMap, array $fields = ['tas_uid']): array
    {
        foreach ($fields as $field) {
            $value = (string) ($entry[$field] ?? '');
            if ($value !== '' && isset($idMap[$value])) {
                $entry[$field] = $idMap[$value];
            }
        }

        return $entry;
    }

    private static function isValidBpmnId(string $id): bool
    {
        return (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/', $id);
    }

    private static function makeValidBpmnId(string $oldId, int $counter): string
    {
        $suffix = substr(preg_replace('/[^A-Za-z0-9_]+/', '', $oldId) ?? '', -8);
        $candidate = 'node_' . ($suffix !== '' ? $suffix : (string) $counter);

        return self::isValidBpmnId($candidate) ? $candidate : 'node_' . $counter;
    }

    /**
     * @param  array<string, string>  $idMap
     */
    private static function remapAttributeValues(DOMXPath $xpath, string $attribute, array $idMap): void
    {
        foreach ($xpath->query('//*[@' . $attribute . ']') as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            $value = $node->getAttribute($attribute);
            if ($value !== '' && isset($idMap[$value])) {
                $node->setAttribute($attribute, $idMap[$value]);
            }
        }
    }

    /**
     * @param  array<string, string>  $idMap
     */
    private static function remapFlowReferenceElements(DOMXPath $xpath, array $idMap): void
    {
        foreach (['incoming', 'outgoing'] as $tag) {
            foreach ($xpath->query('//bpmn:' . $tag) as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }

                $value = trim($node->textContent);
                if ($value !== '' && isset($idMap[$value])) {
                    while ($node->firstChild !== null) {
                        $node->removeChild($node->firstChild);
                    }
                    $node->appendChild($node->ownerDocument->createTextNode($idMap[$value]));
                }
            }
        }
    }

    private static function normalizeFlowNodeChildOrder(DOMXPath $xpath): void
    {
        $flowNodeQuery = '//bpmn:task | //bpmn:userTask | //bpmn:scriptTask'
            . ' | //bpmn:exclusiveGateway | //bpmn:parallelGateway | //bpmn:inclusiveGateway'
            . ' | //bpmn:complexGateway | //bpmn:eventBasedGateway';

        foreach ($xpath->query($flowNodeQuery) as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            self::reorderFlowChildren($node, ['incoming', 'outgoing']);
        }
    }

    /**
     * @param  array<int, string>  $orderedTags
     */
    private static function reorderFlowChildren(DOMElement $node, array $orderedTags): void
    {
        $bpmnNs = self::BPMN_NS;
        $groups = array_fill_keys($orderedTags, []);
        $toRemove = [];

        foreach ($node->childNodes as $child) {
            if (!($child instanceof DOMElement)) {
                continue;
            }

            if ($child->namespaceURI === $bpmnNs && in_array($child->localName, $orderedTags, true)) {
                $groups[$child->localName][] = $child;
                $toRemove[] = $child;
            }
        }

        if ($toRemove === []) {
            return;
        }

        $anchor = $toRemove[0];
        foreach ($toRemove as $child) {
            $node->removeChild($child);
        }

        $insertAt = $anchor;
        foreach ($orderedTags as $tag) {
            foreach ($groups[$tag] as $flowNode) {
                if ($insertAt !== null && $insertAt->parentNode === $node) {
                    $node->insertBefore($flowNode, $insertAt);
                } else {
                    $node->appendChild($flowNode);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    public static function buildTaskElementMap(array $payload, DOMXPath $xpath): array
    {
        $map = [];

        foreach (self::taskElementMapEntries($payload) as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $tasUid = $entry['tas_uid'] ?? null;
            $elementId = $entry['element_id'] ?? $entry['element_uid'] ?? null;

            if (is_string($tasUid) && $tasUid !== '' && is_string($elementId) && $elementId !== '') {
                $map[$tasUid] = $elementId;
            }
        }

        $titlesByName = [];
        foreach ($xpath->query('//bpmn:task[@id] | //bpmn:userTask[@id] | //bpmn:scriptTask[@id]') as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            $elementId = $node->getAttribute('id');
            if ($elementId === '') {
                continue;
            }

            $map[$elementId] ??= $elementId;

            $name = $node->getAttribute('name');
            if ($name === '') {
                continue;
            }

            $titlesByName[strtolower(trim($name))] ??= $elementId;
        }

        foreach ($payload['tasks'] ?? [] as $task) {
            if (!is_array($task)) {
                continue;
            }

            $tasUid = $task['tas_uid'] ?? null;
            $tasTitle = $task['tas_title'] ?? null;

            if (!is_string($tasUid) || $tasUid === '' || isset($map[$tasUid]) || !is_string($tasTitle)) {
                continue;
            }

            $key = strtolower(trim($tasTitle));
            if (isset($titlesByName[$key])) {
                $map[$tasUid] = $titlesByName[$key];
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, mixed>
     */
    private static function taskElementMapEntries(array $payload): array
    {
        return $payload['task_element_map'] ?? [];
    }
}
