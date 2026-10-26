<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Fallback BPMN diagram interchange for XML without BPMNDiagram.
 * PM3 MCP export is the canonical layout source during migration.
 */
final class BpmnDiagramInterchange
{
    private const BPMN_NS = 'http://www.omg.org/spec/BPMN/20100524/MODEL';

    private const BPMNDI_NS = 'http://www.omg.org/spec/BPMN/20100524/DI';

    private const DC_NS = 'http://www.omg.org/spec/DD/20100524/DC';

    private const DI_NS = 'http://www.omg.org/spec/DD/20100524/DI';

    /**
     * Ensure migrated BPMN includes a BPMNDiagram so PM4 Modeler can render the canvas.
     */
    public static function ensure(string $xml): string
    {
        $document = new DOMDocument();
        if (@$document->loadXML($xml) === false) {
            return $xml;
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('bpmn', self::BPMN_NS);

        if ($xpath->query('//*[local-name()="BPMNDiagram"]')->length > 0) {
            return $document->saveXML() ?: $xml;
        }

        return self::appendLayout($document, $xpath) ?: $xml;
    }

    /**
     * Rebuild BPMNDiagram from current process elements (e.g. after MCP adds tasks/flows).
     */
    public static function rebuild(string $xml): string
    {
        $document = new DOMDocument();
        if (@$document->loadXML($xml) === false) {
            return $xml;
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('bpmn', self::BPMN_NS);

        foreach ($xpath->query('//*[local-name()="BPMNDiagram"]') as $diagram) {
            $diagram->parentNode?->removeChild($diagram);
        }

        return self::appendLayout($document, $xpath) ?: $xml;
    }

    private static function appendLayout(DOMDocument $document, DOMXPath $xpath): ?string
    {
        $processNode = $xpath->query('//bpmn:process')->item(0);
        if (!($processNode instanceof DOMElement)) {
            return $document->saveXML();
        }

        $processId = $processNode->getAttribute('id') ?: 'ProcessId';
        $definitions = $document->documentElement;
        if (!($definitions instanceof DOMElement)) {
            return $document->saveXML();
        }

        $layout = self::buildLayout($xpath);
        self::appendDiagram($document, $definitions, $processId, $layout['nodes'], $layout['flows']);

        return $document->saveXML();
    }

    /**
     * @param  array<string, array{x:int,y:int,width:int,height:int}>  $nodeBounds
     * @param  array<string, array<int, array{x:int,y:int}>>  $flowWaypoints
     */
    private static function appendDiagram(
        DOMDocument $document,
        DOMElement $definitions,
        string $processId,
        array $nodeBounds,
        array $flowWaypoints = [],
    ): void {
        if ($nodeBounds === []) {
            return;
        }

        $diagram = $document->createElementNS(self::BPMNDI_NS, 'bpmndi:BPMNDiagram');
        $diagram->setAttribute('id', 'BPMNDiagram_' . $processId);
        $diagram->setAttribute('name', $processId);

        $plane = $document->createElementNS(self::BPMNDI_NS, 'bpmndi:BPMNPlane');
        $plane->setAttribute('bpmnElement', $processId);
        $diagram->appendChild($plane);

        foreach ($nodeBounds as $elementId => $bounds) {
            $shape = $document->createElementNS(self::BPMNDI_NS, 'bpmndi:BPMNShape');
            $shape->setAttribute('id', 'BPMNShape_' . $elementId);
            $shape->setAttribute('bpmnElement', $elementId);

            $dcBounds = $document->createElementNS(self::DC_NS, 'dc:Bounds');
            $dcBounds->setAttribute('x', (string) $bounds['x']);
            $dcBounds->setAttribute('y', (string) $bounds['y']);
            $dcBounds->setAttribute('width', (string) $bounds['width']);
            $dcBounds->setAttribute('height', (string) $bounds['height']);
            $shape->appendChild($dcBounds);
            $plane->appendChild($shape);
        }

        foreach ($flowWaypoints as $flowId => $waypoints) {
            if ($waypoints === []) {
                continue;
            }

            $edge = $document->createElementNS(self::BPMNDI_NS, 'bpmndi:BPMNEdge');
            $edge->setAttribute('id', 'BPMNEdge_' . $flowId);
            $edge->setAttribute('bpmnElement', $flowId);

            foreach ($waypoints as $point) {
                $waypoint = $document->createElementNS(self::DI_NS, 'di:waypoint');
                $waypoint->setAttribute('x', (string) $point['x']);
                $waypoint->setAttribute('y', (string) $point['y']);
                $edge->appendChild($waypoint);
            }

            $plane->appendChild($edge);
        }

        $definitions->appendChild($diagram);
    }

    /**
     * @return array{
     *     nodes: array<string, array{x:int,y:int,width:int,height:int}>,
     *     flows: array<string, array<int, array{x:int,y:int}>>
     * }
     */
    private static function buildLayout(DOMXPath $xpath): array
    {
        $nodes = [];

        foreach ($xpath->query('//*[local-name()="startEvent" or local-name()="endEvent" or local-name()="task" or local-name()="userTask" or local-name()="scriptTask" or local-name()="exclusiveGateway" or local-name()="parallelGateway" or local-name()="inclusiveGateway"]') as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            $id = $node->getAttribute('id');
            if ($id === '') {
                continue;
            }

            $nodes[$id] = self::defaultBounds($node->localName);
        }

        $orderedIds = self::orderNodesByFlow($xpath, $nodes);

        $flowWaypoints = [];
        foreach ($xpath->query('//*[local-name()="sequenceFlow"]') as $flow) {
            if (!($flow instanceof DOMElement)) {
                continue;
            }

            $flowId = $flow->getAttribute('id');
            $source = $flow->getAttribute('sourceRef');
            $target = $flow->getAttribute('targetRef');
            if ($flowId === '' || $source === '' || $target === '') {
                continue;
            }

            $flowWaypoints[$flowId] = self::edgeWaypoints(
                $nodes[$source] ?? self::defaultBounds('task'),
                $nodes[$target] ?? self::defaultBounds('task'),
            );
        }

        $column = 0;
        foreach ($orderedIds as $elementId) {
            $size = $nodes[$elementId];
            $nodes[$elementId] = [
                'x' => 120 + ($column * 180),
                'y' => 120,
                'width' => $size['width'],
                'height' => $size['height'],
            ];
            $column++;
        }

        foreach ($flowWaypoints as $flowId => $points) {
            $flow = $xpath->query('//*[@id="' . $flowId . '"]')->item(0);
            if (!($flow instanceof DOMElement)) {
                continue;
            }

            $source = $flow->getAttribute('sourceRef');
            $target = $flow->getAttribute('targetRef');
            if (!isset($nodes[$source], $nodes[$target])) {
                continue;
            }

            $flowWaypoints[$flowId] = self::edgeWaypoints($nodes[$source], $nodes[$target]);
        }

        return ['nodes' => $nodes, 'flows' => $flowWaypoints];
    }

    /**
     * @param  array<string, array{x:int,y:int,width:int,height:int}>  $nodes
     * @return array<int, string>
     */
    private static function orderNodesByFlow(DOMXPath $xpath, array $nodes): array
    {
        $startIds = [];
        foreach ($xpath->query('//*[local-name()="startEvent"]') as $start) {
            if (!($start instanceof DOMElement)) {
                continue;
            }

            $id = $start->getAttribute('id');
            if ($id !== '') {
                $startIds[] = $id;
            }
        }

        $outgoing = [];
        foreach ($xpath->query('//*[local-name()="sequenceFlow"]') as $flow) {
            if (!($flow instanceof DOMElement)) {
                continue;
            }

            $source = $flow->getAttribute('sourceRef');
            $target = $flow->getAttribute('targetRef');
            if ($source !== '' && $target !== '') {
                $outgoing[$source][] = $target;
            }
        }

        $ordered = [];
        $visited = [];
        $queue = $startIds;

        while ($queue !== []) {
            $id = array_shift($queue);
            if (isset($visited[$id])) {
                continue;
            }

            $visited[$id] = true;
            if (isset($nodes[$id])) {
                $ordered[] = $id;
            }

            foreach ($outgoing[$id] ?? [] as $target) {
                if (!isset($visited[$target])) {
                    $queue[] = $target;
                }
            }
        }

        foreach (array_keys($nodes) as $id) {
            if (!isset($visited[$id])) {
                $ordered[] = $id;
            }
        }

        return $ordered;
    }

    /**
     * @param  array{x:int,y:int,width:int,height:int}  $source
     * @param  array{x:int,y:int,width:int,height:int}  $target
     * @return array<int, array{x:int,y:int}>
     */
    private static function edgeWaypoints(array $source, array $target): array
    {
        $sourceX = $source['x'] + $source['width'];
        $sourceY = $source['y'] + (int) floor($source['height'] / 2);
        $targetX = $target['x'];
        $targetY = $target['y'] + (int) floor($target['height'] / 2);

        return [
            ['x' => $sourceX, 'y' => $sourceY],
            ['x' => $targetX, 'y' => $targetY],
        ];
    }

    /**
     * @return array{x:int,y:int,width:int,height:int}
     */
    private static function defaultBounds(string $localName): array
    {
        return match ($localName) {
            'startEvent', 'endEvent' => ['x' => 0, 'y' => 0, 'width' => 36, 'height' => 36],
            'exclusiveGateway', 'parallelGateway', 'inclusiveGateway', 'complexGateway' => ['x' => 0, 'y' => 0, 'width' => 50, 'height' => 50],
            default => ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 80],
        };
    }
}
