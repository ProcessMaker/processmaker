<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use ProcessMaker\Events\ModelerStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\PackageHelper;
use ProcessMaker\Traits\HasControllerAddons;
use ProcessMaker\Traits\ProcessMapTrait;

class ModelerController extends Controller
{
    use HasControllerAddons;
    use ProcessMapTrait;

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
    public function inflight(ModelerManager $manager, Process $process, ProcessRequest $request)
    {
        event(new ModelerStarting($manager));

        $bpmn = $process->bpmn;
        $filteredCompletedNodes = [];
        $requestInProgressNodes = [];
        $requestIdleNodes = [];

        // Use the process version that was active when the request was started.
        $processRequest = ProcessRequest::find($request->id);
        if ($processRequest) {
            $bpmn = $process->versions()
                ->where('id', $processRequest->process_version_id)
                ->firstOrFail()
                ->bpmn;

            $requestCompletedNodes = $processRequest->tokens()->whereIn('status', ['CLOSED', 'TRIGGERED'])->pluck('element_id');
            $requestInProgressNodes = $processRequest->tokens()->where('status', 'ACTIVE')->pluck('element_id');
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

        return view('processes.modeler.inflight', [
            'manager' => $manager,
            'bpmn' => $bpmn,
            'requestCompletedNodes' => $filteredCompletedNodes,
            'requestInProgressNodes' => $requestInProgressNodes,
            'requestIdleNodes' => $requestIdleNodes,
            'requestId' => $request->id,
        ]);
    }

    /**
     * Invokes the Modeler for In-flight Process Map rendering for ai generative.
     */
    public function inflightProcessAi(ModelerManager $manager, $promptVersionId, Request $request)
    {
        $aiMicroserviceHost = config('app.ai_microservice_host');
        $url = $aiMicroserviceHost . '/pm/getPromptVersion';
        $headers = [
            'Authorization' => 'token',
        ];

        $params = [
            'promptVersionId' => $promptVersionId,
        ];

        $promptVersion = Http::withHeaders($headers)->post($url, $params);

        $bpmn = '';

        if (array_key_exists('version', $promptVersion->json())) {
            $bpmn = $promptVersion->json()['version']['bpmn'];
        }

        event(new ModelerStarting($manager));

        return view('processes.modeler.inflight-generative-ai', [
            'manager' => $manager,
            'bpmn' => $bpmn,
        ]);
    }
}
