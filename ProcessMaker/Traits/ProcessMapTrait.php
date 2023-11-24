<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Collection;
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
     * Fetches completed sequence flows from an XML based on completed and in-progress nodes.
     * It identifies sequence flows where the source is in the completed nodes list
     * and the target is either in-progress or completed.
     * Additionally, it checks for nodes that were in progress but are now completed.
     *
     * @return Collection
     */
    private function getCompletedSequenceFlow(
        SimpleXMLElement $xml,
        ProcessRequest $processRequest,
        string $completedNodesStr,
        string $inProgressNodesStr,
        string $completedInProgressStr
    ) {
        $baseQuery = '//bpmn:sequenceFlow[contains("%s", @sourceRef) and contains("%s", @targetRef)]';

        $inProgressAndCompletedNodesStr = "{$completedNodesStr} {$inProgressNodesStr}";
        $completedQuery = sprintf($baseQuery, $completedNodesStr, $inProgressAndCompletedNodesStr);
        $inProgressQuery = sprintf($baseQuery, $completedInProgressStr, $inProgressAndCompletedNodesStr);
        $query = $completedQuery . ' | ' . $inProgressQuery;

        // Get and transform elements.
        $elements = collect($xml->xpath($query))->map(function ($element) {
            $conditionExpressions = $element->xpath('bpmn:conditionExpression');

            return [
                'id' => (string) $element['id'],
                'conditionExpression' => $conditionExpressions ? (string) $conditionExpressions[0] : null,
            ];
        });

        // Filter based on conditionExpression.
        $data = (array) $processRequest->data;

        return $elements->filter(function ($element) use ($data) {
            $expression = $element['conditionExpression'];
            if ($expression) {
                foreach ($data as $key => $value) {
                    $expression = str_replace($key, var_export($value, true), $expression);
                }

                try {
                    return eval("return {$expression};");
                } catch (Throwable $e) {
                    return false;
                }
            }

            return true;
        })->pluck('id');
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
}
