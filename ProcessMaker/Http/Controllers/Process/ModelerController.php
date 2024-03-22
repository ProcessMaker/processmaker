<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use ProcessMaker\Events\ModelerStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenType;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\Package\Cdata\Http\Controllers\Api\CdataController;
use ProcessMaker\Package\PackagePmBlocks\Http\Controllers\Api\PmBlockController;
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
        $pmBlockList = $this->getPmBlockList();
        $externalIntegrationsList = $this->getExternalIntegrationsList();

        /*
         * Emit the ModelerStarting event, passing in our ModelerManager instance. This will
         * allow packages to add additional javascript for modeler initialization which
         * can customize the modeler controls list.
         */
        event(new ModelerStarting($manager));

        // For create subprocess modal in modeler
        $countProcessCategories = ProcessCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();

        // For create screen modal in modeler
        $screenTypes = [];
        foreach (ScreenType::pluck('name')->toArray() as $type) {
            $screenTypes[$type] = __(ucwords(strtolower($type)));
        }
        asort($screenTypes);
        $countScreenCategories = ScreenCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();
        $isProjectsInstalled = PackageHelper::isPackageInstalled(PackageHelper::PM_PACKAGE_PROJECTS);
        $isPackageAiInstalled = hasPackage('package-ai');

        // For create script modal in modeler
        $scriptExecutors = ScriptExecutor::list();
        $countScriptCategories = ScriptCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();

        $draft = $process->versions()->draft()->first();
        if ($draft) {
            $process->fill($draft->only(['svg', 'bpmn']));
        }

        $runAsUserDefault = User::where('is_administrator', true)->first();

        return view('processes.modeler.index', [
            'process' => $process->append('notifications', 'task_notifications'),
            'manager' => $manager,
            'signalPermissions' => SignalManager::permissions($request->user()),
            'autoSaveDelay' => config('versions.delay.process', 5000),
            'isVersionsInstalled' => PackageHelper::isPackageInstalled('ProcessMaker\Package\Versions\PluginServiceProvider'),
            'isDraft' => $draft !== null,
            'pmBlockList' => $pmBlockList,
            'externalIntegrationsList' => $externalIntegrationsList,
            'screenTypes' => $screenTypes,
            'scriptExecutors' => $scriptExecutors,
            'countProcessCategories' => $countProcessCategories,
            'countScreenCategories' => $countScreenCategories,
            'countScriptCategories' => $countScriptCategories,
            'isProjectsInstalled' => $isProjectsInstalled,
            'isPackageAiInstalled' => $isPackageAiInstalled,
            'isAiGenerated' => request()->query('ai'),
            'runAsUserDefault' => $runAsUserDefault,
        ]);
    }

    /**
     * Invokes the Modeler for In-flight Process Map rendering.
     */
    public function inflight(ModelerManager $manager, Process $process, ProcessRequest $request)
    {
        // Use the process version that was active when the request was started. PR #4934
        $processRequest = ProcessRequest::find($request->id);

        return $this->renderInflight($manager, $process, $processRequest, $request->id);
    }

    /**
     * Invokes the Modeler for In-flight Process Map.
     *
     * This method is required by package-testing to overwrite the 3rd parameter ProcessRequest $request parameter.
     */
    public function renderInflight(ModelerManager $manager, Process $process, $processRequest, $processRequestId)
    {
        $pmBlockList = $this->getPmBlockList();
        $externalIntegrationsList = $this->getExternalIntegrationsList();

        event(new ModelerStarting($manager));

        $bpmn = $process->bpmn;
        $filteredCompletedNodes = [];
        $requestInProgressNodes = [];
        $requestIdleNodes = [];

        if ($processRequest) {
            $bpmn = $process->versions()
                ->where('id', $processRequest->process_version_id)
                ->firstOrFail()
                ->bpmn;

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

        return view('processes.modeler.inflight', [
            'manager' => $manager,
            'bpmn' => $bpmn,
            'requestCompletedNodes' => $filteredCompletedNodes,
            'requestInProgressNodes' => $requestInProgressNodes,
            'requestIdleNodes' => $requestIdleNodes,
            'requestId' => $processRequestId,
            'pmBlockList' => $pmBlockList,
            'externalIntegrationsList' => $externalIntegrationsList,
        ]);
    }

    /**
     * Load PMBlock list
     */
    private function getPmBlockList()
    {
        $pmBlockList = null;
        if (hasPackage('package-pm-blocks')) {
            $controller = new PmBlockController();
            $newRequest = new Request(['per_page' => 10000]);
            $response = $controller->index($newRequest);
            if ($response->response($newRequest)->status() === 200) {
                $pmBlockList = json_decode($response->response()->content())->data;
            }
        }

        return $pmBlockList;
    }

    /**
     * Load External Integrations list
     */
    private function getExternalIntegrationsList()
    {
        $externalIntegrationsList = null;
        if (hasPackage('package-cdata')) {
            $controller = new CdataController();
            $newRequest = new Request(['per_page' => 10]);
            $response = $controller->index($newRequest);
            if ($response->getStatusCode() === 200) {
                $externalIntegrationsList = json_decode($response->getContent());
            }
        }

        return $externalIntegrationsList;
    }

    /**
     * Invokes the Modeler for In-flight Process Map rendering for ai generative.
     */
    public function inflightProcessAi(ModelerManager $manager, $promptVersionId, $choiceNumber, $type, Request $request)
    {
        $version = $this->getVersion($promptVersionId, $type);
        $bpmn = '';
        $choicesCount = 0;

        if (array_key_exists('version', $version->json())) {
            if (array_key_exists('bpmn', $version->json()['version'])) {
                $bpmn = $version->json()['version']['bpmn'];
            }
            if (array_key_exists('choices', $version->json()['version'])) {
                $bpmn = $version->json()['version']['choices'][$choiceNumber]['bpmn'];
                $choicesCount = count($version->json()['version']['choices']);
            }
        }

        event(new ModelerStarting($manager));

        return view('processes.modeler.inflight-generative-ai', [
            'manager' => $manager,
            'bpmn' => $bpmn,
            'choiceNumber' => $choiceNumber,
            'choicesCount' => $choicesCount,
        ]);
    }

    private function getVersion($promptVersionId, $type) {
        $aiMicroserviceHost = config('app.ai_microservice_host');
        $url = $aiMicroserviceHost . '/pm/getPromptVersion';

        $headers = [
            'Authorization' => 'token',
        ];
        $params = [
            'promptVersionId' => $promptVersionId,
        ];

        if ($type === 'processVersion') {
            $url = $aiMicroserviceHost . '/pm/getProcessVersion';
            $params = [
                'processVersionId' => $promptVersionId,
            ];
        }
        
        return Http::withHeaders($headers)->post($url, $params);
    }
}
