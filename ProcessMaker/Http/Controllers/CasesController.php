<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Http\Controllers\Controller;
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
        $currentUser = Auth::user()->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);

        // This is a temporary API the engine team will provide the new
        return view('cases.casesMain', compact('currentUser'));
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
        $processRequest = ProcessRequest::find($request->id);

        $bpmn = $request->process->bpmn;
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
       

        $inflightData = [
            'bpmn' => $bpmn,
            'requestCompletedNodes' => $filteredCompletedNodes,
            'requestInProgressNodes' => $requestInProgressNodes,
            'requestIdleNodes' => $requestIdleNodes,
            'requestId' => $request->process->id,
        ];
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
            'bpmn',
            'inflightData',
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
}
