<?php

namespace ProcessMaker\Nayra\Services;

use DOMDocument;
use DOMXPath;
use Exception;

class FixBpmnSchemaService
{
    /**
     * Fix BPMN definition if is necesasary
     *
     * @param string $bpmn
     * @return string
     * @throws \Exception
     */
    public static function fix(string $bpmn): string
    {
        try {
            // Create main object
            $document = new DOMDocument();
            $document->preserveWhiteSpace = false;
            $document->loadXml($bpmn);

            // Get root node
            $root = $document->documentElement;

            // Set "targetNamespace" attribute always
            $root->setAttribute('targetNamespace', 'http://www.omg.org/spec/BPMN/20100524/MODEL');

            // Create XPath object
            $xpath = new DOMXPath($document);

            // Register all namespaces, including default
            foreach ($document->documentElement->attributes as $attr) {
                if (strpos($attr->nodeName, 'xmlns:') === 0) {
                    $prefix = substr($attr->nodeName, 6);
                    $xpath->registerNamespace($prefix, $attr->nodeValue);
                } elseif ($attr->nodeName === 'xmlns') {
                    // Register default namespace as "def"
                    $xpath->registerNamespace('def', $attr->nodeValue);
                }
            }

            // Find all task nodes
            $taskNodes = $xpath->query('//*[local-name()="task"]');

            foreach ($taskNodes as $task) {
                $taskId = $task->getAttribute("id");
                $taskPrefix = !empty($task->prefix) ? "$task->prefix:" : '';
                $taskNS = $task->namespaceURI;

                $dataInputAssociation = $xpath->query('./*[local-name()="dataInputAssociation"]', $task)->item(0);
                if (!$dataInputAssociation) {
                    continue;
                }

                // Skip if targetRef already exists
                $targetRefExists = $xpath->query('./*[local-name()="targetRef"]', $dataInputAssociation)->length > 0;
                if ($targetRefExists) {
                    continue;
                }

                // Extract sourceRef
                $sourceRef = $xpath->query('./*[local-name()="sourceRef"]', $dataInputAssociation)->item(0);
                if (!$sourceRef) {
                    throw new Exception("sourceRef not found in dataInputAssociation for task $taskId");
                }
                $sourceId = $sourceRef->nodeValue;

                // Create ioSpecification and children
                $ioSpec = $document->createElementNS($taskNS, "{$taskPrefix}ioSpecification");
                $ioSpecId = "{$taskId}_inner_" . round(microtime(true) * 1000);
                $ioSpec->setAttribute("id", $ioSpecId);

                $dataInputId = "data_input_{$sourceId}";
                $dataInput = $document->createElementNS($taskNS, "{$taskPrefix}dataInput");
                $dataInput->setAttribute("id", $dataInputId);
                $dataInput->setAttribute("name", "Template for protocol");
                $ioSpec->appendChild($dataInput);

                $inputSet = $document->createElementNS($taskNS, "{$taskPrefix}inputSet");
                $inputSet->setAttribute("id", "{$taskId}_inner_" . (round(microtime(true) * 1000) + 2));
                $dataInputRefs = $document->createElementNS($taskNS, "{$taskPrefix}dataInputRefs", $dataInputId);
                $inputSet->appendChild($dataInputRefs);
                $ioSpec->appendChild($inputSet);

                $outputSet = $document->createElementNS($taskNS, "{$taskPrefix}outputSet");
                $outputSet->setAttribute("id", "{$taskId}_inner_" . (round(microtime(true) * 1000) + 3));
                $ioSpec->appendChild($outputSet);

                $task->insertBefore($ioSpec, $dataInputAssociation);

                // Add targetRef
                $targetRef = $document->createElementNS($taskNS, "{$taskPrefix}targetRef", $dataInputId);
                $dataInputAssociation->appendChild($targetRef);

                // Add BPMNEdge to BPMNDiagram
                $diagramNodes = $xpath->query('//*[local-name() = "BPMNDiagram"]');
                if ($diagramNodes->length === 0) {
                    throw new Exception("No BPMNDiagram node found in the BPMN file.");
                }
                $bpmnDiagram = $diagramNodes->item(0);
                $diagramPrefix = $bpmnDiagram->prefix;
                $diagramNS = $bpmnDiagram->namespaceURI;

                $bpmnPlaneNodes = $xpath->query('.//*[local-name() = "BPMNPlane"]', $bpmnDiagram);
                if ($bpmnPlaneNodes->length === 0) {
                    throw new Exception("No BPMNPlane found inside BPMNDiagram.");
                }
                $bpmnPlane = $bpmnPlaneNodes->item(0);

                $edgeId = 'BPMNEdge_' . $dataInputAssociation->getAttribute("id");
                $bpmnEdge = $document->createElementNS($diagramNS, "{$diagramPrefix}:BPMNEdge");
                $bpmnEdge->setAttribute("id", $edgeId);
                $bpmnEdge->setAttribute("bpmnElement", $dataInputAssociation->getAttribute("id"));

                $diNS = 'http://www.omg.org/spec/DD/20100524/DI';
                $waypoint1 = $document->createElementNS($diNS, "di:waypoint");
                $waypoint1->setAttribute("x", "100");
                $waypoint1->setAttribute("y", "100");

                $waypoint2 = $document->createElementNS($diNS, "di:waypoint");
                $waypoint2->setAttribute("x", "200");
                $waypoint2->setAttribute("y", "200");

                $bpmnEdge->appendChild($waypoint1);
                $bpmnEdge->appendChild($waypoint2);
                $bpmnPlane->appendChild($bpmnEdge);
            }

            return $document->saveXml();
        } catch (Exception $e) {
            throw $e;
        }
    }
}
