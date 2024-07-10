<?php

namespace ProcessMaker\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Filters\SaveSession;
use ProcessMaker\Helpers\DefaultColumns;
use ProcessMaker\Helpers\MobileHelper;
use ProcessMaker\Http\Controllers\Api\ProcessRequestController;
use ProcessMaker\Jobs\MarkNotificationAsRead;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\UserResourceView;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
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

        $defaultColumns = DefaultColumns::get('tasks');

        $taskDraftsEnabled = TaskDraft::draftsEnabled();

        return view('tasks.index', compact('title', 'userFilter', 'defaultColumns', 'taskDraftsEnabled'));
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
        $screenFields = $screenVersion ? $screenVersion->screenFilteredFields() : [];

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
                    'screenFields' => $screenFields,
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
                'screenFields' => $screenFields,
                'taskDraftsEnabled' => TaskDraft::draftsEnabled(),
            ]);
        }
    }

    public function quickFillEdit(ProcessRequestToken $task)
    {
        $screenVersion = $task->getScreenVersion();
        $screenFields = $screenVersion ? $screenVersion->screenFilteredFields() : [];

        return view('tasks.editQuickFill', [
            'task' => $task,
            'screenFields' => $screenFields,
        ]);
    }

    /**
     * Update variable.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $abe_uuid
     */
    public function updateVariable(HttpRequest $request, $abe_uuid)
    {
        // Validar los parámetros GET
        $request->validate([
            'varName' => 'required|string',
            'varValue' => 'required|string',
        ]);

        $response = [
            'message' => 'An error occurred',
            'data' => null,
            'status' => 500
        ];

        try {
            // Verificar si la respuesta ya ha sido enviada
            $abe = ProcessAbeRequestToken::where('uuid', $abe_uuid)->first();
            // Check if the token is available
            if (!$abe) {
                $response['message'] = 'Token not found';
                $response['status'] = 404;
            }
            // Review if the autentication is required
            if ($abe->require_login && !Auth::check()) {
                $response['message'] = 'Authentication required';
                $response['status'] = 403;
            }
            if ($abe->is_answered) {
                $response['message'] = 'This response has already been answered';
                $response['data'] = $abe;
                $response['status'] = 200;
            } else {
                // Get the token related
                $task = ProcessRequestToken::find($abe->process_request_token_id);
                if (!$task) {
                    $response['message'] = 'Process request token not found';
                    $response['status'] = 404;
                } else {
                    // Update the data
                    $data = $abe->data ? json_decode($abe->data, true) : [];
                    $data[$request->varName] = $request->varValue;
                    $abe->data = json_encode($data);
                    // Define the answered_at and is_answered
                    $abe->is_answered = true;
                    $abe->answered_at = Carbon::now();
                    // Review if the user is autenticated
                    if (Auth::check()) {
                        $abe->user_id = Auth::id();
                    }
                    $abe->save();
                    // Define the parameter for complete the task
                    $process = $abe->process_id;
                    $instance = $task->processRequest;
                    // Completar la tarea relacionada
                    WorkflowManager::completeTask(
                        $process,
                        $instance,
                        $task,
                        $data
                    );

                    // Set the flag is_actionbyemail in true
                    (new ProcessRequestController)->enableIsActionbyemail($task->id);

                    $response['message'] = 'Variable updated successfully';
                    $response['data'] = $abe;
                    $response['status'] = 200;
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating variable',
                'error' => $e->getMessage()
            ], 500);
        }
        // Return response
        return response()->json([
            'message' => $response['message'],
            'data' => $response['data']
        ], $response['status']);
    }
}
