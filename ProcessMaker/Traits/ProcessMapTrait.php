<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Collection;
use ProcessMaker\Bpmn\Process;
use ProcessMaker\Models\ProcessRequest;
use SimpleXMLElement;

trait ProcessMapTrait
{
    /**
     * Load XML from a string and register its namespaces.
     * This function will help to prepare the XML for further processing.
     */
    private function loadAndPrepareXML(string $bpmn): SimpleXMLElement
    {
        $xml = simplexml_load_string($bpmn);
        $namespaces = $xml->getNamespaces(true);

        // Register the BPMN namespace explicitly
        $xml->registerXPathNamespace('bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL');

        foreach ($namespaces as $prefix => $ns) {
            $xml->registerXPathNamespace($prefix, $ns);
        }

        return $xml;
    }

    /**
     * Get the maximum token ID for a given element ID.
     */
    private function getMaxTokenId(ProcessRequest $request, ?string $elementId): ?int
    {
        return $request->tokens()
            ->where('element_id', $elementId)
            ->max('id');
    }

    /**
     * Get the token count for a given element ID.
     */
    private function getTokenCount(ProcessRequest $request, string $elementId): int
    {
        return $request->tokens()
            ->where([
                'element_id' => $elementId,
                'process_request_id' => $request->id,
            ])
            ->count();
    }

    /**
     * Filter the XML using the provided XPath query and return a Collection of string values.
     */
    private function filterXML(SimpleXMLElement $xml, string $xpathQuery): Collection
    {
        $elements = $xml->xpath($xpathQuery);

        return collect(array_map('strval', $elements));
    }

    /**
     * Filter the XML to get IDs of all nodes excluding "lanes" and "pools" nodes.
     */
    private function getNodeIds(SimpleXMLElement $xml): Collection
    {
        $query = '//*[name() != "bpmn:lane" and name() != "bpmn:participant"]/@id';

        return $this->filterXML($xml, $query);
    }

    /**
     * Performs an XPath query to get sequenceFlow elements
     * whose 'sourceRef' attribute is in the string of completed nodes
     * and 'targetRef' attribute is in the string of in-progress and completed nodes
     * also validates Nodes in progress that were completed before to obtain their paths.
     */
    private function getCompletedSequenceFlow(SimpleXMLElement $xml, string $completedNodesStr, string $inProgressNodesStr, string $completedInProgressNode): Collection
    {
        $inProgressAndCompletedNodes = $completedNodesStr . ' ' . $inProgressNodesStr;
        $query = '//bpmn:sequenceFlow[contains("' . $completedNodesStr . '", @sourceRef) and contains("' . $inProgressAndCompletedNodes . '", @targetRef)]/@id';
        $query = $query . ' | //bpmn:sequenceFlow[contains("' . $completedInProgressNode . '", @sourceRef) and contains("' . $inProgressAndCompletedNodes . '", @targetRef)]/@id';

        return $this->filterXML($xml, $query);
    }

    /**
     * Performs an XPath query to get the targetRef and SourceRef Node Id
     */
    private function getRefNodes(SimpleXMLElement $xml, string $sequenceFlowId): Collection
    {
        $sequenceFlowNode = $xml->xpath("//bpmn:sequenceFlow[@id='{$sequenceFlowId}']");

        if (empty($sequenceFlowNode)) {
            return collect();
        }

        return collect([
            'targetRef' => (string) $sequenceFlowNode[0]['targetRef'],
            'sourceRef' => (string) $sequenceFlowNode[0]['sourceRef'],
        ]);
    }

    /**
     * Validates if the sourceRef token is in progress when the repeat count is the same as the targetRef
     */
    private function getCountFlag(int $sourceCount, int $targetCount, string $sourceRef, ProcessRequest $request) :bool
    {
        $maxToken = $request->tokens()->find($this->getMaxTokenId($request, $sourceRef));

        return $maxToken->status === 'ACTIVE' && $sourceCount === $targetCount;
    }

    private function loadProcessMap(ProcessRequest $request): array
    {
        $processRequest = ProcessRequest::find($request->id);
        // Get the bpmn related to the version
        $bpmn = $request->process->versions()
            ->where('id', $request->process_version_id)
            ->select('bpmn')
            ->firstOrFail()
            ->bpmn;
        $filteredCompletedNodes = [];
        $requestInProgressNodes = [];
        $requestIdleNodes = [];

        if ($processRequest) {
            $requestCompletedNodes = $processRequest->tokens()
                ->whereIn('status', ['CLOSED', 'COMPLETED', 'TRIGGERED'])
                ->pluck('element_id');
            $requestInProgressNodes = $processRequest->tokens()
                ->whereIn('status', ['ACTIVE', 'INCOMING'])
                ->pluck('element_id');

            // Remove any node that is 'ACTIVE' from the completed list.
            $filteredCompletedNodes = $requestCompletedNodes->diff($requestInProgressNodes)->values();

            // Obtain In-Progress nodes that were completed before
            $matchingNodes = $requestInProgressNodes->intersect($requestCompletedNodes);

            // Get idle nodes.
            $xml = $this->loadAndPrepareXML($bpmn);
            $nodeIds = $this->getNodeIds($xml);
            $requestIdleNodes = $nodeIds->diff($filteredCompletedNodes)->diff($requestInProgressNodes)->values();

            // Add completed sequence flow to the list of completed nodes.
            $sequenceFlowNodes = $this->getCompletedSequenceFlow($xml, $filteredCompletedNodes->implode(' '), $requestInProgressNodes->implode(' '), $matchingNodes->implode(' '));
            $filteredCompletedNodes = $filteredCompletedNodes->merge($sequenceFlowNodes);
        }

        return [
            'bpmn' => $bpmn,
            'requestCompletedNodes' => $filteredCompletedNodes,
            'requestInProgressNodes' => $requestInProgressNodes,
            'requestIdleNodes' => $requestIdleNodes,
            'requestId' => $request->id,
        ];
    }
}
