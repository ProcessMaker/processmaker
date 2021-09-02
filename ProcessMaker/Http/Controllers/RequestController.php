<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenVersion;
use ProcessMaker\Models\Process;
use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use ProcessMaker\Traits\HasControllerAddons;
use ProcessMaker\Traits\SearchAutocompleteTrait;
use ProcessMaker\Package\PackageComments\PackageServiceProvider;

class RequestController extends Controller
{
    use HasMediaTrait;
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

        $title = 'My Requests';

        $types = ['all'=>'All Requests','in_progress'=>'Requests In Progress','completed'=>'Completed Requests'];

        if(array_key_exists($type,$types)){
          $title = $types[$type];
        }

        $currentUser = Auth::user()->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);

        return view('requests.index', compact(
            ['type','title', 'currentUser']
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
                    $allowInterstitial = filter_var($definition['allowInterstitial'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }
                if ($allowInterstitial && $request->user_id == Auth::id()) {
                    $active = $request->tokens()
                        ->where('user_id', Auth::id())
                        ->where('element_type', 'task')
                        ->where('status', 'ACTIVE')
                        ->orderBy('id')->first();
                    return redirect(route('tasks.edit', ['task' => $active ? $active->getKey() : $startEvent->getKey()]));
                }
            }
        }

        $userHasCommentsForRequest = Comment::where('commentable_type', ProcessRequest::class)
                ->where('commentable_id', $request->id)
                ->where('body','like', '%@' . \Auth::user()->username . '%')
                ->count() > 0;

        $requestMedia = $request->media()->get()->pluck('id');
        $userHasCommentsForMedia = Comment::where('commentable_type', \ProcessMaker\Models\Media::class)
                ->whereIn('commentable_id', $requestMedia)
                ->where('body','like', '%@' . \Auth::user()->username . '%')
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

        $canCancel = Auth::user()->can('cancel', $request->process);
        $canViewComments = (Auth::user()->hasPermissionsFor('comments')->count() > 0) || class_exists(PackageServiceProvider::class);
        $canManuallyComplete = Auth::user()->is_administrator && $request->status === 'ERROR';

        $files = $request->getMedia();

        $canPrintScreens = $this->canUserPrintScreen($request);
        $screenRequested = $canPrintScreens ? $request->getScreensRequested() : [];

        $manager = app(ScreenBuilderManager::class);
        event(new ScreenBuilderStarting($manager, ($request->summary_screen) ? $request->summary_screen->type : 'FORM'));

        $addons = $this->getPluginAddons('edit', compact(['request']));

        return view('requests.show', compact(
            'request', 'files', 'canCancel', 'canViewComments', 'canManuallyComplete', 'manager', 'canPrintScreens', 'screenRequested', 'addons'
        ));
    }

    /**
     * Redirect to owner task of a subprocess
     *
     * @param ProcessRequest $request
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function showOwner(ProcessRequest $request)
    {
        $ownerTask = $request->ownerTask;
        if (!$ownerTask) {
            return abort(404);
        }
        $interstitial = $ownerTask->getInterstitial();
        if ($interstitial['allow_interstitial']) {
            return redirect(route('tasks.edit', ['task' => $ownerTask->id]));
        } else {
            return redirect(route('requests.show', ['request' => $request->id]));
        }
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

        return false;
    }

    public function downloadFiles(ProcessRequest $request, Media $media)
    {
        $ids = $request->getMedia()->pluck('id');
        if (!$ids->contains($media->id)) {
            abort(403);
        }
        return response()->download($media->getPath(), $media->file_name);
    }
}
