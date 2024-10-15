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
use ProcessMaker\ProcessTranslations\ScreenTranslation;
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
    public function edit(ProcessRequest $request)
    {
        if (!request()->input('skipInterstitial') && $request->status === 'ACTIVE') {
            $startEvent = $request->tokens()->orderBy('id')->first();
            if ($startEvent) {
                $definition = $startEvent->getDefinition();
                $allowInterstitial = false;
                if (isset($definition['allowInterstitial'])) {
                    $allowInterstitial = filter_var(
                        $definition['allowInterstitial'],
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    );
                }
                if ($allowInterstitial && $request->user_id == Auth::id() && request()->has('fromTriggerStartEvent')) {
                    $active = $request->tokens()
                        ->where('user_id', Auth::id())
                        ->where('element_type', 'task')
                        ->where('status', 'ACTIVE')
                        ->orderBy('id')->first();

                    // If the interstitial is enabled on the start event, then use it as the task
                    if ($active) {
                        $task = $allowInterstitial ? $startEvent : $active;
                    } else {
                        $task = $startEvent;
                    }

                    return redirect(route('tasks.edit', [
                        'task' => $task->getKey(),
                    ]));
                }
            }
        }

        $userHasCommentsForRequest = Comment::where('commentable_type', ProcessRequest::class)
                ->where('commentable_id', $request->id)
                ->where('body', 'like', '%{{' . \Auth::user()->id . '}}%')
                ->count() > 0;

        $requestMedia = $request->media()->get()->pluck('id');

        $userHasCommentsForMedia = Comment::where('commentable_type', \ProcessMaker\Models\Media::class)
                ->whereIn('commentable_id', $requestMedia)
                ->where('body', 'like', '%{{' . \Auth::user()->id . '}}%')
                ->count() > 0;

        if (!$userHasCommentsForMedia && !$userHasCommentsForRequest) {
            $this->authorize('view', $request);
        }

        $request->participants;
        $request->user;
        $request->summary = $request->summary();

        if ($request->status === 'CANCELED' && $request->process->cancel_screen_id) {
            $request->summary_screen = $request->process->cancelScreen;
        } else {
            $request->summary_screen = $request->getSummaryScreen();
        }
        $request->request_detail_screen = Screen::find($request->process->request_detail_screen_id);

        $canCancel = Auth::user()->can('cancel', $request->processVersion);
        $canViewComments = (Auth::user()->hasPermissionsFor('comments')->count() > 0) || class_exists(PackageServiceProvider::class);
        $canManuallyComplete = Auth::user()->is_administrator && $request->status === 'ERROR';
        $canRetry = false;

        if ($canManuallyComplete) {
            $retry = RetryProcessRequest::for($request);

            $canRetry = $retry->hasRetriableTasks() &&
                !$retry->hasNonRetriableTasks() &&
                !$retry->isChildRequest();
        }

        $files = \ProcessMaker\Models\Media::getFilesRequest($request);

        $canPrintScreens = $this->canUserPrintScreen($request);

        $manager = app(ScreenBuilderManager::class);
        event(new ScreenBuilderStarting($manager, ($request->summary_screen) ? $request->summary_screen->type : 'FORM'));

        $addons = [];
        $dataActionsAddons = [];

        $isProcessManager = $request->process?->manager_id === Auth::user()->id;

        $eligibleRollbackTask = null;
        $errorTask = RollbackProcessRequest::getErrorTask($request);
        if ($errorTask) {
            $eligibleRollbackTask = RollbackProcessRequest::eligibleRollbackTask($errorTask);
        }
        $this->summaryScreenTranslation($request);
        return view('cases.edit', compact(
            'request',
            'files',
            'canCancel',
            'canViewComments',
            'canManuallyComplete',
            'canRetry',
            'manager',
            'canPrintScreens',
            'addons',
            'isProcessManager',
            'eligibleRollbackTask',
            'errorTask',
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
            $screenArray = $request->summary_screen->toArray();
            $screenTranslation = new ScreenTranslation();
            $request->summary_screen['config'] = $screenTranslation->applyTranslations($screenArray);
        }
    }
}
