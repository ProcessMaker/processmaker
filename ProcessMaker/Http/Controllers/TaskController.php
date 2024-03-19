<?php

namespace ProcessMaker\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Filters\SaveSession;
use ProcessMaker\Helpers\MobileHelper;
use ProcessMaker\Jobs\MarkNotificationAsRead;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\UserResourceView;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Package\SavedSearch\Http\Controllers\SavedSearchController;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use ProcessMaker\Traits\HasControllerAddons;
use ProcessMaker\Traits\SearchAutocompleteTrait;

class TaskController extends Controller
{
    use SearchAutocompleteTrait;
    use HasControllerAddons;

    private static $dueLabels = [
        'open' => 'Due',
        'completed' => 'Completed',
        'overdue' => 'Due',
    ];

    public function index()
    {
        $title = 'To Do Tasks';

        if (Request::input('status') == 'CLOSED') {
            $title = 'Completed Tasks';
        }

        if (isset($_SERVER['HTTP_USER_AGENT']) && MobileHelper::isMobile($_SERVER['HTTP_USER_AGENT'])) {
            return view('tasks.mobile', compact('title'));
        }

        $userFilter = SaveSession::getConfigFilter('taskFilter', Auth::user());

        // Get default Saved search config
        if (class_exists(SavedSearch::class)) {
            $defaultSavedSearch = SavedSearch::firstSystemSearchFor(
                Auth::user(),
                SavedSearch::KEY_TASKS,
            );
            if ($defaultSavedSearch) {
                $defaultColumns = SavedSearchController::adjustColumnsOf(
                    $defaultSavedSearch->columns,
                    SavedSearch::TYPE_TASK
                );
            } else {
                $defaultColumns = null;
            }
        } else {
            $defaultColumns = null;
        }

        return view('tasks.index', compact('title', 'userFilter', 'defaultColumns'));
    }

    public function edit(ProcessRequestToken $task, string $preview = '')
    {
        $task = $task->loadTokenInstance();
        $dataManager = new DataManager();
        $userHasComments = Comment::where('commentable_type', ProcessRequestToken::class)
                                    ->where('commentable_id', $task->id)
                                    ->where('body', 'like', '%{{' . \Auth::user()->id . '}}%')
                                    ->count() > 0;

        if (!\Auth::user()->can('update', $task) && !$userHasComments) {
            $this->authorize('update', $task);
        }

        //Mark notification as read
        MarkNotificationAsRead::dispatch([['url', '=', '/' . Request::path()]], ['read_at' => Carbon::now()]);

        $manager = app(ScreenBuilderManager::class);
        event(new ScreenBuilderStarting($manager, $task->getScreenVersion() ? $task->getScreenVersion()->type : 'FORM'));

        $submitUrl = route('api.tasks.update', $task->id);
        $task->processRequest;
        $task->user;
        $screenVersion = $task->getScreenVersion();
        $task->component = $screenVersion ? $screenVersion->parent->renderComponent() : null;
        $task->screen = $screenVersion ? $screenVersion->toArray() : null;
        $task->request_data = $dataManager->getData($task);
        $task->bpmn_tag_name = $task->getBpmnDefinition()->localName;
        $interstitial = $task->getInterstitial();
        $task->interstitial_screen = $interstitial['interstitial_screen'];
        $task->allow_interstitial = $interstitial['allow_interstitial'];
        $task->definition = $task->getDefinition();
        $task->requestor = $task->processRequest->user;
        $task->draft = $task->draft();
        $element = $task->getDefinition(true);

        if ($element instanceof ScriptTaskInterface) {
            return redirect(route('requests.show', ['request' => $task->processRequest->getKey()]));
        } else {
            if (!empty($preview)) {
                return view('tasks.preview', [
                    'task' => $task,
                    'dueLabels' => self::$dueLabels,
                    'manager' => $manager,
                    'submitUrl' => $submitUrl,
                    'files' => $task->processRequest->requestFiles(),
                    'addons' => $this->getPluginAddons('edit', []),
                    'assignedToAddons' => $this->getPluginAddons('edit.assignedTo', []),
                    'dataActionsAddons' => $this->getPluginAddons('edit.dataActions', []),
                    'screenFields' => $screenVersion ? $screenVersion->fields->map(fn ($field) => $field->field) : [],
                ]);
            }

            if (isset($_SERVER['HTTP_USER_AGENT']) && MobileHelper::isMobile($_SERVER['HTTP_USER_AGENT'])) {
                return view('tasks.editMobile', [
                    'task' => $task,
                    'dueLabels' => self::$dueLabels,
                    'manager' => $manager,
                    'submitUrl' => $submitUrl,
                    'files' => $task->processRequest->requestFiles(),
                    'addons' => $this->getPluginAddons('edit', []),
                    'assignedToAddons' => $this->getPluginAddons('edit.assignedTo', []),
                    'dataActionsAddons' => $this->getPluginAddons('edit.dataActions', []),
                ]);
            }

            UserResourceView::setViewed(Auth::user(), $task);
            $currentUser = Auth::user()->only([
                'id',
                'username',
                'fullname',
                'firstname',
                'lastname',
                'avatar',
                'timezone',
                'datetime_format',
            ]);

            return view('tasks.edit', [
                'task' => $task,
                'dueLabels' => self::$dueLabels,
                'manager' => $manager,
                'submitUrl' => $submitUrl,
                'files' => $task->processRequest->requestFiles(),
                'addons' => $this->getPluginAddons('edit', []),
                'assignedToAddons' => $this->getPluginAddons('edit.assignedTo', []),
                'dataActionsAddons' => $this->getPluginAddons('edit.dataActions', []),
                'currentUser' => $currentUser,
            ]);
        }
    }
}
