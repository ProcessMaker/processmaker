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
use ProcessMaker\Models\Media as MediaModel;
use ProcessMaker\Models\ScreenVersion;
use ProcessMaker\Models\UserResourceView;
use ProcessMaker\Package\PackageComments\PackageServiceProvider;
use ProcessMaker\ProcessTranslations\ProcessTranslation;
use ProcessMaker\RetryProcessRequest;
use ProcessMaker\Traits\HasControllerAddons;
use ProcessMaker\Traits\SearchAutocompleteTrait;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RequestController extends Controller
{
    use SearchAutocompleteTrait;
    use HasControllerAddons;

    /**
     * Get the list of requests.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index($type = null)
    {
        if ($type === 'all') {
            $this->authorize('view-all_requests');
        }

        $title = 'My Cases';

        $types = ['all'=>'All Requests', 'in_progress'=>'Requests In Progress', 'completed'=>'Completed Requests'];

        if (array_key_exists($type, $types)) {
            $title = $types[$type];
        }

        $currentUser = Auth::user()->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);

        if (isset($_SERVER['HTTP_USER_AGENT']) && MobileHelper::isMobile($_SERVER['HTTP_USER_AGENT'])) {
            return view('requests.mobile', compact(
                ['type', 'title', 'currentUser']
            ));
        }

        $requestType = '';
        if (!empty($type)) {
            $requestType = '|' . $type;
        }
        $userFilter = SaveSession::getConfigFilter('requestFilter' . $requestType, Auth::user());

        $defaultColumns = DefaultColumns::get('requests');

        return view('requests.index', compact(
            ['type', 'title', 'currentUser', 'userFilter', 'defaultColumns']
        ));
    }

    /**
     * Request Show
     *
     * @param ProcessRequest $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(ProcessRequest $request, Media $mediaItems)
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

        $addons = $this->getPluginAddons('edit', compact(['request']));
        $dataActionsAddons = $this->getPluginAddons('edit.dataActions', []);

        $isProcessManager = $request->process?->manager_id === Auth::user()->id;

        $eligibleRollbackTask = null;
        $errorTask = RollbackProcessRequest::getErrorTask($request);
        if ($errorTask) {
            $eligibleRollbackTask = RollbackProcessRequest::eligibleRollbackTask($errorTask);
        }
        $this->summaryScreenTranslation($request);

        if (isset($_SERVER['HTTP_USER_AGENT']) && MobileHelper::isMobile($_SERVER['HTTP_USER_AGENT'])) {
            return view('requests.showMobile', compact(
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

        UserResourceView::setViewed(Auth::user(), $request);

        return view('requests.show', compact(
            'request',
            'files',
            'canCancel',
            'canViewComments',
            'canManuallyComplete',
            'canRetry',
            'manager',
            'canPrintScreens',
            'addons',
            'dataActionsAddons',
            'isProcessManager',
            'eligibleRollbackTask',
            'errorTask',
        ));
    }

    public function screenPreview(ProcessRequest $request, ProcessRequestToken $task, ScreenVersion $screen)
    {
        $this->authorize('view', $request);
        if (!$this->canUserPrintScreen($request)) {
            //user without permissions
            return redirect('403');
        }

        $dataManager = new DataManager();
        $data = $dataManager->getData($task);

        $manager = app(ScreenBuilderManager::class);
        event(new ScreenBuilderStarting($manager, ($request->summary_screen) ? $request->summary_screen->type : 'FORM'));

        return view('requests.preview', compact('request', 'screen', 'manager', 'data'));
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

    public function downloadFiles(ProcessRequest $request, $media)
    {
        $model = MediaModel::find($media);
        $file = $request->downloadFile($media);

        if ($file) {
            // Register the Event
            FilesDownloaded::dispatch($model, $request);

            return response()->download($file);
        }

        return abort(response(__('File ID does not exist'), 404));
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
