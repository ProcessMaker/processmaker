<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\ModelerStarting;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Controllers\Process\ModelerController;
use ProcessMaker\Managers\ModelerManager;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Screen;
use ProcessMaker\Package\PackageComments\PackageServiceProvider;
use ProcessMaker\ProcessTranslations\ScreenTranslation;
use ProcessMaker\Traits\ProcessMapTrait;

class CasesController extends Controller
{
    use ProcessMapTrait;

    /**
     * Get the list of requests.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        $manager = app(ScreenBuilderManager::class);
        event(new ScreenBuilderStarting($manager, 'FORM'));
        $currentUser = Auth::user()->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);

        // This is a temporary API the engine team will provide the new
        return view('cases.casesMain', compact('currentUser', 'manager'));
    }

    /**
     * Cases Detail
     *
     * @param ProcessRequest $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($case_number)
    {
        // Load event ScreenBuilderStarting
        $manager = app(ScreenBuilderManager::class);
        event(new ScreenBuilderStarting($manager, 'FORM'));
        // Load event ModelerStarting
        $managerModeler = app(ModelerManager::class);
        event(new ModelerStarting($managerModeler));

        // "Initialload.js" file causes an issue related to SVG in the modeler
        // The other scripts are not needed in the case detail
        $scriptsDisabled = ['package-slideshow', 'package-process-optimization', 'package-ab-testing', 'package-testing', 'initialLoad'];
        $managerModelerScripts = array_filter($managerModeler->getScripts(), function ($script) use ($scriptsDisabled) {
            foreach ($scriptsDisabled as $enabledScript) {
                if (strpos($script, $enabledScript) !== false) {
                    return false;
                }
            }

            return true;
        });

        // Get all the request related to this case number
        $allRequests = ProcessRequest::where('case_number', $case_number)->get();
        $parentRequest = null;
        $requestCount = $allRequests->count();
        // Search the parent request parent_request_id and load $request
        foreach ($allRequests as $request) {
            if (is_null($request->parent_request_id)) {
                $parentRequest = $request;
                break;
            }
        }
        $request->participants;
        $request->user;
        // Load the data and key values
        $request->summary = $request->summary();
        // Load the screen configured in "Cancel Screen"
        if ($request->status === 'CANCELED' && $request->process->cancel_screen_id) {
            $request->summary_screen = $request->process->cancelScreen;
        } else {
            $request->summary_screen = $request->getSummaryScreen();
        }
        //Stage data
        $currentStages = $this->getCurrentStage($request->last_stage_id, $request->last_stage_name);
        $allStages = $this->getStagesByProcessId($request->process_id);
        $progressStage = $this->getProgressStage($allStages, $currentStages);
        // Load the screen configured in "Request Detail Screen"
        $request->request_detail_screen = Screen::find($request->process->request_detail_screen_id);
        // The user canCancel if has the processPermission and the case has only one request
        $canCancel = (Auth::user()->can('cancel', $request->processVersion) && $requestCount === 1);
        // The user can see the comments
        $canViewComments = (Auth::user()->hasPermissionsFor('comments')->count() > 0) || class_exists(PackageServiceProvider::class);
        // The user is Manager from the main request
        $isProcessManager = $request->process?->manager_id === Auth::user()->id;
        // Check if the user has permission print for request
        $canPrintScreens = $canOpenCase = $this->canUserCanOpenCase($allRequests);
        if (!$canOpenCase && !$isProcessManager) {
            $this->authorize('view', $request);
        }

        // Get the summary screen tranlations
        $this->summaryScreenTranslation($request);

        // Load the process map
        $inflightData = $this->loadProcessMap($request);
        $bpmn = $inflightData['bpmn'];

        // Get all PM-Blocks
        $modelerController = new ModelerController();
        $pmBlockList = $modelerController->getPmBlockList();

        // Return the view
        return view('cases.edit', compact(
            'request',
            'parentRequest',
            'requestCount',
            'canCancel',
            'canViewComments',
            'canPrintScreens',
            'isProcessManager',
            'manager',
            'managerModelerScripts',
            'bpmn',
            'inflightData',
            'pmBlockList',
            'progressStage',
            'currentStages',
        ));
    }

    /**
     * The user can open the case
     *
     * @param \Illuminate\Database\Eloquent\Collection $allRequests
     * @return bool
     */
    private function canUserCanOpenCase($allRequests)
    {
        // Validate user is administrator
        if (Auth::user()->is_administrator) {
            return true;
        }

        // Any user with permissions Edit Request Data, Edit Task Data and view All Requests
        if (Auth::user()->can('view-all_requests') && Auth::user()->can('edit-request_data') && Auth::user()->can('edit-task_data')) {
            return true;
        }

        // Validate user is participant or requester in the request related to the case
        foreach ($allRequests as $request) {
            $participantIds = $request->participants->pluck('id')->toArray();
            if (in_array(Auth::user()->id, $participantIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Translates the summary screen strings
     * @param ProcessRequest $request
     * @return void
     */
    public function summaryScreenTranslation(ProcessRequest $request): void
    {
        if ($request->summary_screen) {
            $screenTranslation = new ScreenTranslation();
            if (get_class($request->summary_screen) === Screen::class) {
                $request->summary_screen['config'] = $screenTranslation->applyTranslations($request->summary_screen->getLatestVersion());
            } else {
                $request->summary_screen['config'] = $screenTranslation->applyTranslations($request->summary_screen);
            }
        }
    }

    /**
     * Get the current stages from a JSON string.
     *
     * This method decodes a JSON string representing stages into an associative array.
     * If the input is null or not a valid JSON string, it returns an empty array.
     *
     * @param int|null $id The ID of the stage.
     * @param string|null $name The name of the stage.
     * @return array An associative array of stages if the JSON is valid;
     *               otherwise, an empty array.
     */
    public static function getCurrentStage(?int $id, ?string $name): array
    {
        // Initialize currentStages as an empty array
        $currentStages = [];

        // Check if $id is not null and $name is a valid string
        if (!is_null($id) && is_string($name)) {
            $currentStages = [
                'stage_id' => $id,
                'stage_name' => $name,
            ];
        }

        return $currentStages;
    }

    /**
     * Calculate the progress of stages.
     *
     * @param array $allStages An array of all stages.
     * @param array $currentStages An array of current stages.
     * @return float The progress percentage (0 to 100).
     */
    public static function getProgressStage(array $allStages, array $currentStages): float
    {
        // Total number of stages
        $totalStages = count($allStages);

        // If there are no stages, return 0% progress
        if ($totalStages === 0) {
            return 0.0;
        }

        // Total number of current stages
        $totalCurrentStages = count($currentStages);

        // If there are no stages, return 0% progress
        if ($totalCurrentStages === 0) {
            return 0.0;
        }

        // Count the number of completed stages
        $completedStages = 0;
        // Extract the current stage IDs from the currentStages array
        $currentStageId = $currentStages['stage_id'];

        foreach ($allStages as $stage) {
            var_dump($currentStageId);
            $completedStages++;
            // Check if the current stage ID is in the current stages
            if ($stage['id'] === $currentStageId) {
                break; // Exit the loop once the stage is found
            }
        }

        // Calculate progress percentage
        $progressPercentage = ($completedStages / $totalStages) * 100;

        return round($progressPercentage, 2);
    }
}
