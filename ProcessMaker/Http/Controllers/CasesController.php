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
    public function index($type = null)
    {
       // This is a temporary API the engine team will provide the new
        return view('cases.casesMain');
    }
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

        $addons = $this->getPluginAddons('edit', compact(['request']));
        $dataActionsAddons = $this->getPluginAddons('edit.dataActions', []);

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
}
