<?php

namespace ProcessMaker\Http\Controllers;

use Facades\ProcessMaker\RollbackProcessRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use ProcessMaker\Cache\CacheRemember;
use ProcessMaker\Events\FilesDownloaded;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Filters\SaveSession;
use ProcessMaker\Helpers\DefaultColumns;
use ProcessMaker\Helpers\MobileHelper;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenVersion;
use ProcessMaker\Models\UserResourceView;
use ProcessMaker\Package\PackageComments\PackageServiceProvider;
use ProcessMaker\ProcessTranslations\ProcessTranslation;
use ProcessMaker\RetryProcessRequest;
use ProcessMaker\Traits\HasControllerAddons;
use ProcessMaker\Traits\SearchAutocompleteTrait;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CasesController extends Controller
{
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
    public function edit($case_number)
    {
        // Get all the request related to this case number
        $requests = ProcessRequest::where('case_number', $case_number)->get();
        $parentRequest = null;
        $requestCount = $requests->count();
        // Search the parent request  parent_request_id
        foreach ($requests as $request) {
            if (is_null($request->parent_request_id)) {
                $parentRequest = $request;
                break;
            }
        }
        $request->participants;
        $request->user;
        $request->summary = [];
        // The user canCancel if has the processPermission and the case has only one request
        $canCancel = (Auth::user()->can('cancel', $request->processVersion) && $requestCount === 1);
        // The user can see the comments
        $canViewComments = (Auth::user()->hasPermissionsFor('comments')->count() > 0) || class_exists(PackageServiceProvider::class);
        // Check if the user has permission print for request
        $canPrintScreens = $this->canUserPrintScreen($request);
        // The user is Manager
        $isProcessManager = $request->process?->manager_id === Auth::user()->id;
        // Get the summary screen tranlations
        $this->summaryScreenTranslation($request);

        // Return the view
        return view('cases.edit', compact(
            'request',
            'parentRequest',
            'requestCount',
            'canCancel',
            'canViewComments',
            'canPrintScreens',
            'isProcessManager',
        ));
    }

    /**
     * the user may or may not print forms
     *
     * @param ProcessRequest $request
     * @return bool
     */
    private function canUserPrintScreen(ProcessRequest $request)
    {
        //validate user is administrator
        if (Auth::user()->is_administrator) {
            return true;
        }

        //validate user is participant or requester
        if (in_array(Auth::user()->id, $request->participants()->get()->pluck('id')->toArray())) {
            return true;
        }

        // Any user with permissions Edit Request Data, Edit Task Data and view All Requests
        if (Auth::user()->can('view-all_requests') && Auth::user()->can('edit-request_data') && Auth::user()->can('edit-task_data')) {
            return true;
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
            $processTranslation = new ProcessTranslation($request->process);
            $translatedConf = $processTranslation->applyTranslations($request->summary_screen);
            $request->summary_screen['config'] = $translatedConf;
        }
    }
}
