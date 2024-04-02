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
     * Display the modeler interface for a specific process.
     *
     * This method renders the modeler interface for a specific process,
     * allowing users to view and edit the process in the modeler.
     * It checks for any plugin addons to customize the display,
     * and if found, renders the custom blade view with the provided alternatives.
     * Otherwise, it renders the default modeler interface view with prepared data.
     *
     * @param ModelerManager $manager The ModelerManager instance.
     * @param Process $process The Process instance to be displayed.
     * @param Request $request The current HTTP request.
     * @return \Illuminate\View\View The view for the modeler interface.
     */
    public function show(ModelerManager $manager, Process $process, Request $request)
    {
        // Default view for the modeler interface
        $defaultView = 'processes.modeler.index';

        // Get plugin addons for the 'show' action
        $addons = $this->getPluginAddons('show', []);

        // Retrieve custom blade from addons
        $customBlade = ($addons[0] ?? [])['new-blade'] ?? null;

        // If a custom blade view is provided, render the custom view
        if ($customBlade) {
            return view($customBlade, $this->prepareModelerData($manager, $process, $request, 'A'));
        }

        // Otherwise, render the default modeler interface view with prepared data
        return view($defaultView, $this->prepareModelerData($manager, $process, $request, 'A'));
    }
    /**
     * Prepare data for displaying a process in the modeler.
     *
     * This method prepares data required for displaying a process in the modeler interface,
     * including process information, modeler manager instance, signal permissions, auto-save delay,
     * and other necessary details for creating subprocess, screen, and script modals in the modeler.
     * It also checks if certain packages are installed and handles draft versions of the process.
     *
     * @param ModelerManager $manager The ModelerManager instance.
     * @param Process $process The Process instance to be displayed.
     * @param Request $request The current HTTP request.
     * @return array The prepared data array containing process information, manager instance,
     *               signal permissions, auto-save delay, package installation status, draft status,
     *               block list, external integrations list, screen types, script executors,
     *               category counts, and other relevant information.
     */
    public function prepareModelerData(ModelerManager $manager, Process $process, Request $request, string $alternative)
    {
        // Initializing variables
        $processA = $processB = null;
        $startingPointsA = $startingPointsB = [];
        $startingPoints = ['A' => [], 'B' => []];
        $resumePointsA = $resumePointsB = [];
        $resumePoints = ['A' => [], 'B' => []];

        // Retrieve PM block list and external integrations list
        $pmBlockList = $this->getPmBlockList();
        $externalIntegrationsList = $this->getExternalIntegrationsList();

        // Emit ModelerStarting event to allow customization of modeler controls
        event(new ModelerStarting($manager));

        // Count process categories for creating subprocess modal
        $countProcessCategories = ProcessCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();

        // Retrieve screen types and count screen categories for creating screen modal
        $screenTypes = ScreenType::pluck('name')->map(fn($type) => __(ucwords(strtolower($type))))->sort()->toArray();
        $countScreenCategories = ScreenCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();

        // Check if Projects and AI packages are installed
        $isProjectsInstalled = PackageHelper::isPackageInstalled(PackageHelper::PM_PACKAGE_PROJECTS);
        $isPackageAiInstalled = hasPackage('package-ai');

        // Retrieve script executors and count script categories for creating script modal
        $scriptExecutors = ScriptExecutor::list();
        $countScriptCategories = ScriptCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();

        // Retrieve draft version of the process
        $draft = $process->getDraftOrPublishedLatestVersion($alternative);
        if ($draft) {
            $process->fill($draft->only(['svg', 'bpmn']));
        }

        // Retrieve the default user for running processes
        $runAsUserDefault = User::where('is_administrator', true)->first();

        // Append notifications to the process
        $process->append('notifications', 'task_notifications');

        // Load the alternative if the package is installed
        if (class_exists('ProcessMaker\Package\PackageABTesting\Models\Alternative')) {
            $process->load('alternativeInfo');

            // Load the another alternative
            if ($process->alternative === 'A') {
                $processAlternative = $process->getLatestVersion('B');
                if (!is_null($processAlternative)) {
                    $processB = Process::find($process->id);
                    $processB->bpmn = $processAlternative->bpmn;
                }
                $processA = $process;
            } elseif ($process->alternative === 'B') {
                $processAlternative = $process->getLatestVersion('A');
                if (!is_null($processAlternative)) {
                    $processA = Process::find($process->id);
                    $processA->bpmn = $processAlternative->bpmn;
                }
                $processB = $process;
            }

            // Force to get updated start events
            if (!is_null($processA)) {
                $processA->start_events = $processA->getUpdatedStartEvents();
            }
            if (!is_null($processB)) {
                $processB->start_events = $processB->getUpdatedStartEvents();
            }

            // Get all starting points
            if (!empty($processA)) {
                if (!empty($processA->start_events)) {
                    foreach ($processA->start_events as $startEvent) {
                        $startingPointsA[] = [
                            'id' => $startEvent['id'],
                            'name' => $startEvent['name'],
                            'type' => 'startEvent',
                        ];
                    }
                }
                foreach ($processA->getTasks() as $task) {
                    $startingPointsA[] = [
                        'id' => $task['id'],
                        'name' => $task['name'],
                        'type' => 'task',
                    ];
                }
            }

            if (!empty($processB)) {
                if (!empty($processB->start_events)) {
                    foreach ($processB->start_events as $startEvent) {
                        $startingPointsB[] = [
                            'id' => $startEvent['id'],
                            'name' => $startEvent['name'],
                            'type' => 'startEvent',
                        ];
                    }
                }
                foreach ($processB->getTasks() as $task) {
                    $startingPointsB[] = [
                        'id' => $task['id'],
                        'name' => $task['name'],
                        'type' => 'task',
                    ];
                }
            }

            $startingPoints = [
                'A' => $startingPointsA,
                'B' => $startingPointsB,
            ];

            // Get all resume points
            if (!empty($processA)) {
                foreach ($processA->getTasks() as $task) {
                    if ($task['type'] === 'task' || $task['type'] === 'userTask' || $task['type'] === 'manualTask') {
                        $resumePointsA[] = [
                            'id' => $task['id'],
                            'name' => $task['name'],
                            'type' => $task['type'],
                        ];
                    }
                }
            }

            if (!empty($processB)) {
                foreach ($processB->getTasks() as $task) {
                    if ($task['type'] === 'task' || $task['type'] === 'userTask' || $task['type'] === 'manualTask') {
                        $resumePointsB[] = [
                            'id' => $task['id'],
                            'name' => $task['name'],
                            'type' => $task['type'],
                        ];
                    }
                }
            }

            $resumePoints = [
                'A' => $resumePointsA,
                'B' => $resumePointsB,
            ];
        }

        return [
            'process' => $process,
            'manager' => $manager,
            'signalPermissions' => SignalManager::permissions($request->user()),
            'autoSaveDelay' => config('versions.delay.process', 5000),
            'isVersionsInstalled' =>
                PackageHelper::isPackageInstalled('ProcessMaker\Package\Versions\PluginServiceProvider'),
            'isDraft' => $draft !== null,
            'draftAlternative' => $draft !== null ? $draft->alternative : null,
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
            'alternative' => $alternative,
            'abPublish' =>
                PackageHelper::isPackageInstalled('ProcessMaker\Package\PackageABTesting\PackageServiceProvider'),
            'startingPoints' => $startingPoints,
            'resumePoints' => $resumePoints,
        ];
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
