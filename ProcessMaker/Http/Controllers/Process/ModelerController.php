<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use ProcessMaker\Events\ModelerStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\PackageHelper;
use ProcessMaker\Traits\HasControllerAddons;
use SimpleXMLElement;

class ModelerController extends Controller
{
    use HasControllerAddons;

    /**
     * Invokes the Process Modeler for rendering.
     */
    public function show(ModelerManager $manager, Process $process, Request $request)
    {
        /*
         * Emit the ModelerStarting event, passing in our ModelerManager instance. This will
         * allow packages to add additional javascript for modeler initialization which
         * can customize the modeler controls list.
         */
        event(new ModelerStarting($manager));

        $draft = $process->versions()->draft()->first();
        if ($draft) {
            $process->fill($draft->only(['svg', 'bpmn']));
        }

        return view('processes.modeler.index', [
            'process' => $process->append('notifications', 'task_notifications'),
            'manager' => $manager,
            'signalPermissions' => SignalManager::permissions($request->user()),
            'autoSaveDelay' => config('versions.delay.process', 5000),
            'isVersionsInstalled' => PackageHelper::isPackageInstalled('ProcessMaker\Package\Versions\PluginServiceProvider'),
            'isDraft' => $draft !== null,
        ]);
    }

    /**
     * Invokes the Modeler for In-flight Process Map rendering.
     */
    public function inflight(ModelerManager $manager, Process $process, Request $request)
    {
        event(new ModelerStarting($manager));

        $bpmn = $process->bpmn;
        $requestCompletedNodes = [];
        $requestInProgressNodes = [];
        $requestIdleNodes = [];

        // Use the process version that was active when the request was started.
        $processRequest = ProcessRequest::find($request->request_id);
        if ($processRequest) {
            $bpmn = $process->versions()
                ->where('id', $processRequest->process_version_id)
                ->firstOrFail()
                ->bpmn;

            $requestCompletedNodes = $processRequest->tokens()->whereIn('status', ['CLOSED', 'TRIGGERED'])->pluck('element_id');
            $requestInProgressNodes = $processRequest->tokens()->where('status', 'ACTIVE')->pluck('element_id');
            // Remove any node that is 'ACTIVE' from the 'CLOSED' list.
            $filteredCompletedNodes = $requestCompletedNodes->diff($requestInProgressNodes)->values();

            // Get idle nodes.
            $xml = $this->loadAndPrepareXML($bpmn);
            $nodeIds = $this->getNodeIds($xml);
            $requestIdleNodes = $nodeIds->diff($filteredCompletedNodes)->diff($requestInProgressNodes)->values();

            // Add completed sequence flow to the list of completed nodes.
            $sequenceFlowNodes = $this->getCompletedSequenceFlow($xml, $filteredCompletedNodes->implode(' '), $requestInProgressNodes->implode(' '));
            $filteredCompletedNodes = $filteredCompletedNodes->merge($sequenceFlowNodes);
        }

        return view('processes.modeler.inflight', [
            'manager' => $manager,
            'process' => $process,
            'bpmn' => $bpmn,
            'requestCompletedNodes' => $filteredCompletedNodes,
            'requestInProgressNodes' => $requestInProgressNodes,
            'requestIdleNodes' => $requestIdleNodes,
        ]);
    }

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
     * Filter the XML to get IDs of all nodes excluding "lanes" and "pools" nodes.
     */
    private function getNodeIds(SimpleXMLElement $xml): Collection
    {
        $elements = $xml->xpath('//*[name() != "bpmn:lane" and name() != "bpmn:participant"]/@id');

        return collect(array_map('strval', $elements));
    }

    /**
     * Performs an XPath query to get sequenceFlow elements
     * whose 'sourceRef' attribute is in the string of completed nodes
     * and 'targetRef' attribute is in the string of in-progress and completed nodes.
     */
    private function getCompletedSequenceFlow(SimpleXMLElement $xml, string $completedNodesStr, string $inProgressNodesStr): Collection
    {
        $inProgressAndCompletedNodes = $completedNodesStr . ' ' . $inProgressNodesStr;
        $elements = $xml->xpath('//bpmn:sequenceFlow[contains("' . $completedNodesStr . '", @sourceRef) and contains("' . $inProgressAndCompletedNodes . '", @targetRef)]/@id');

        return collect(array_map('strval', $elements));
    }
}
