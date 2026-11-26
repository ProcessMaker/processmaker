<?php

namespace ProcessMaker\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Filters\SaveSession;
use ProcessMaker\Helpers\AbeRecipientSignature;
use ProcessMaker\Helpers\AbeResponder;
use ProcessMaker\Helpers\DefaultColumns;
use ProcessMaker\Helpers\MobileHelper;
use ProcessMaker\Http\Controllers\Api\ProcessRequestController;
use ProcessMaker\Http\Controllers\Api\UserConfigurationController;
use ProcessMaker\Jobs\MarkNotificationAsRead;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\UserResourceView;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Services\SmartExtractConfiguration;
use ProcessMaker\Traits\HasControllerAddons;
use ProcessMaker\Traits\SearchAutocompleteTrait;
use ProcessMaker\Traits\TaskControllerIndexMethods;

class TaskController extends Controller
{
    use SearchAutocompleteTrait;
    use HasControllerAddons;
    use TaskControllerIndexMethods;

    private static $dueLabels = [
        'open' => 'Due',
        'completed' => 'Completed',
        'overdue' => 'Due',
    ];

    public function __construct(private readonly SmartExtractConfiguration $smartExtractConfiguration)
    {
    }

    public function index()
    {
        $routerPath = Request::route('router');

        $title = 'To Do Tasks';
        $path = Request::path() !== 'inbox';
        $showOldTaskScreen = $path === true && $routerPath === null;
        $selectedProcess = $routerPath === null ? 'inbox' : 'reload';
        if (Request::input('status') == 'CLOSED') {
            $title = 'Completed Tasks';
        }

        if (isset($_SERVER['HTTP_USER_AGENT']) && MobileHelper::isMobile($_SERVER['HTTP_USER_AGENT'])) {
            return view('tasks.mobile', compact('title'));
        }

        $manager = new ScreenBuilderManager();
        event(new ScreenBuilderStarting($manager, 'FORM'));
        event(new ScreenBuilderStarting($manager, 'DISPLAY'));

        $userFilter = SaveSession::getConfigFilter('taskFilter', Auth::user());

        $defaultColumns = DefaultColumns::get('tasks');

        $isDefaultColumns = DefaultColumns::verifyDefaultColumns($defaultColumns, 'tasks');

        $taskDraftsEnabled = TaskDraft::draftsEnabled();

        $userConfiguration = (new UserConfigurationController())->index()['ui_configuration'] ?? [];

        $currentUser = Auth::user();

        $defaultSavedSearchId = $this->getDefaultSavedSearchId();

        $metricsApiEndpoint = '';
        // If TCE customization is enabled, get the metrics API endpoint
        if (config('app.tce_customization_enable') == 'true') {
            $metricsApiEndpoint = EnvironmentVariable::getMetricsApiEndpoint();
        }

        return view('tasks.index', compact(
            // User and Authentication related
            'currentUser',
            'userFilter',
            'userConfiguration',
            // Process and Task related
            'selectedProcess',
            'defaultColumns',
            'isDefaultColumns',
            'taskDraftsEnabled',
            'showOldTaskScreen',
            // Search and Display
            'defaultSavedSearchId',
            'manager',
            // General
            'title',
            // Metrics API Endpoint
            'metricsApiEndpoint',
        ));
    }

    public function edit(ProcessRequestToken $task, string $preview = '')
    {
        $task = $task->loadTokenInstance();
        $dataManager = new DataManager();

        if (!\Auth::user()->can('update', $task)) {
            $userHasComments = Comment::where('commentable_type', ProcessRequestToken::class)
                                        ->where('commentable_id', $task->id)
                                        ->where('body', 'like', '%{{' . \Auth::user()->id . '}}%')
                                        ->count() > 0;

            if (!$userHasComments) {
                $this->authorize('update', $task);
            }
        }

        //Mark notification as read
        MarkNotificationAsRead::dispatch([['url', '=', '/' . Request::path()]], ['read_at' => Carbon::now()]);

        $manager = app(ScreenBuilderManager::class);
        $screenVersion = $task->getScreenVersion();
        event(new ScreenBuilderStarting($manager, $screenVersion ? $screenVersion->type : 'FORM'));

        $submitUrl = route('api.tasks.update', $task->id);
        $task->processRequest;
        $task->user;
        $task->component = $screenVersion ? $screenVersion->parent->renderComponent() : null;
        $task->screen = $screenVersion ? $screenVersion->toArray() : null;
        $task->request_data = $dataManager->getData($task);
        $task->bpmn_tag_name = $task->getBpmnDefinition()->localName;
        $interstitial = $task->getInterstitial();
        $task->interstitial_screen = $interstitial['interstitial_screen'];
        $task->allow_interstitial = $interstitial['allow_interstitial'];
        $task->definition = $task->getDefinition();
        $task->requestor = $task->processRequest->user;
        $task->draft = $task->draft;
        $element = $task->getDefinition(true);
        $screenFields = $screenVersion ? $screenVersion->screenFilteredFields() : [];
        $taskDraftsEnabled = TaskDraft::draftsEnabled();
        $isSmartExtractTask = $task->element_name === 'Manual Document Review';
        // Remove screen parent to reduce the size of the response
        $screen = $task->screen;
        $screen['parent'] = null;
        $task->screen = $screen;

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
                    'screenFields' => $screenFields,
                    'taskDraftsEnabled' => $taskDraftsEnabled,
                ]);
            }

            dispatch(function () use ($task) {
                UserResourceView::setViewed(Auth::user(), $task);
            })->afterResponse();
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
            $userConfiguration = app(UserConfigurationController::class)->index();
            [$hitlEnabled, $iframeSrc] = $this->smartExtractHitlConfiguration($task, $isSmartExtractTask);
            $canUpdateTask = Auth::user()->can('update', $task);

            return view('tasks.edit', [
                'task' => $task,
                'canUpdateTask' => $canUpdateTask,
                'dueLabels' => self::$dueLabels,
                'manager' => $manager,
                'submitUrl' => $submitUrl,
                'files' => $task->processRequest->requestFiles(),
                'addons' => $this->getPluginAddons('edit', []),
                'assignedToAddons' => $this->getPluginAddons('edit.assignedTo', []),
                'dataActionsAddons' => $this->getPluginAddons('edit.dataActions', []),
                'currentUser' => $currentUser,
                'screenFields' => $screenFields,
                'taskDraftsEnabled' => $taskDraftsEnabled,
                'userConfiguration' => $userConfiguration,
                'hitlEnabled' => $hitlEnabled,
                'iframeSrc' => $iframeSrc,
                'caseNumber' => $task->processRequest->case_number,
                'tceEnableCaseNumberScreen' => config('app.tce_enable_case_number_screen'),
            ]);
        }
    }

    private function smartExtractHitlConfiguration(
        ProcessRequestToken $task,
        bool $isSmartExtractTask
    ): array {
        $hitlEnabled = $this->smartExtractConfiguration->hitlEnabled()
            && $isSmartExtractTask
            && !$task->is_self_service;
        if (!$hitlEnabled) {
            return [false, null];
        }

        $dashboardUrl = $this->smartExtractConfiguration->dashboardUrl();
        $requestData = $task->processRequest->data ?? [];
        $documentToken = $requestData['documentToken'] ?? null;
        $fileId = $requestData['fileId'] ?? null;

        if (!$documentToken || !$fileId || empty($dashboardUrl)) {
            return [true, null];
        }

        return [true, $dashboardUrl . '?' . http_build_query([
            'documentToken' => $documentToken,
            'fileId' => $fileId,
        ])];
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
     * Short-lived cookie that points at a cache entry with the full ABE click query.
     * Needed because showLoginForm clears the session cookie, so url.intended alone
     * is not reliable for preserving signed recipient params across Require Login.
     */
    public const ABE_CLICK_TICKET_COOKIE = 'abe_click_ticket';

    private const ABE_CLICK_CACHE_PREFIX = 'abe_click:';

    private const ABE_CLICK_CACHE_TTL_MINUTES = 20;

    /**
     * Update variable.
     *
     * @param HttpRequest $request
     * @param string $abe_uuid
     */
    public function updateVariable(HttpRequest $request, $abe_uuid)
    {
        try {
            return $this->handleAbeVariableUpdate($request, $abe_uuid);
        } catch (ValidationException $e) {
            // Missing/invalid query after a failed Require Login restore must not become HTTP 500.
            throw $e;
        } catch (\Exception $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Error updating variable',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return $this->returnErrorResponse(__('An error occurred while processing your response.'), 500);
        }
    }

    /**
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    private function handleAbeVariableUpdate(HttpRequest $request, string $abeUuid)
    {
        $abe = ProcessAbeRequestToken::where('uuid', $abeUuid)->first();
        if (!$abe) {
            return $this->returnErrorResponse(__('Token not found'), 404, __('This link could not be found.'));
        }

        $this->restoreAbeClickQuery($request, $abe);

        $request->validate([
            'varName' => 'required|string',
            'varValue' => 'required|string',
        ]);

        if ($abe->require_login && Auth::user()->username === AnonymousUser::ANONYMOUS_USERNAME) {
            return $this->redirectAbeClickToLogin($request, $abe);
        }

        if ($abe->is_answered) {
            return $this->returnErrorResponse(
                __('This response has already been answered'),
                200,
                __('This task has already been answered. No changes were made.')
            );
        }

        $task = ProcessRequestToken::find($abe->process_request_token_id);
        $taskError = $this->abeVariableUpdateTaskError($task);
        if ($taskError !== null) {
            return $taskError;
        }

        return $this->completeAbeVariableUpdate($request, $abe, $task);
    }

    private function abeVariableUpdateTaskError(?ProcessRequestToken $task)
    {
        if (!$task) {
            return $this->returnErrorResponse(
                __('Process request token not found'),
                404,
                __('This task could not be found.')
            );
        }

        if ($task->status === 'CLOSED') {
            return $this->returnErrorResponse(
                __('Task already closed'),
                404,
                __('This task is already closed.')
            );
        }

        return null;
    }

    /**
     * @return \Illuminate\Http\Response|\Illuminate\View\View
     */
    private function completeAbeVariableUpdate(
        HttpRequest $request,
        ProcessAbeRequestToken $abe,
        ProcessRequestToken $task
    ) {
        $data = [
            $request->varName => $request->varValue,
        ];
        if ($this->abeTrackResponderEnabled($task)) {
            $data['abe_responder'] = AbeResponder::fromRequest($request, $abe->uuid);
        }

        $abe->data = json_encode($data);
        $abe->is_answered = true;
        $abe->answered_at = Carbon::now();
        if (Auth::check()) {
            $abe->user_id = Auth::id();
        }
        $abe->save();

        $process = Process::find($task->process_id);
        $instance = $task->processRequest;
        (new ProcessRequestController)->enableIsActionbyemail($task->id);
        WorkflowManager::completeTask(
            $process,
            $instance,
            $task,
            $data
        );

        return $this->showScreen($abe->completed_screen_id);
    }

    /**
     * Whether the task's Actions by Email config opted into responder tracking.
     */
    private function abeTrackResponderEnabled(ProcessRequestToken $task): bool
    {
        $definition = $task->getDefinition();
        if (!is_array($definition)) {
            return false;
        }

        $configEmail = $definition['configEmail'] ?? null;
        if (is_string($configEmail)) {
            $configEmail = json_decode($configEmail, true);
        }

        return is_array($configEmail) && !empty($configEmail['trackResponder']);
    }

    /**
     * Park the full ABE click query in cache and send the user to login.
     * Login clears the session cookie, so a short ticket cookie is the durable key.
     */
    private function redirectAbeClickToLogin(HttpRequest $request, ProcessAbeRequestToken $abe)
    {
        $ticket = Str::random(40);
        Cache::put(
            self::ABE_CLICK_CACHE_PREFIX . $ticket,
            [
                'abe_uuid' => $abe->uuid,
                'query' => $request->query(),
            ],
            now()->addMinutes(self::ABE_CLICK_CACHE_TTL_MINUTES)
        );

        // Keep a short intended URL; query is restored from cache after login.
        $intended = route('tasks.abe.update', ['token_abe' => $abe->uuid]);
        $request->session()->put('url.intended', $intended);

        $sameSite = config('session.same_site') ?: 'lax';
        $intendedCookie = cookie(
            'processmaker_intended',
            $intended,
            self::ABE_CLICK_CACHE_TTL_MINUTES,
            '/',
            null,
            true,
            true,
            false,
            $sameSite
        );
        $ticketCookie = cookie(
            self::ABE_CLICK_TICKET_COOKIE,
            $ticket,
            self::ABE_CLICK_CACHE_TTL_MINUTES,
            '/',
            null,
            true,
            true,
            false,
            $sameSite
        );

        return redirect('login')->withCookie($intendedCookie)->withCookie($ticketCookie);
    }

    /**
     * Merge a parked ABE click query back onto the request when the signature is missing.
     */
    private function restoreAbeClickQuery(HttpRequest $request, ProcessAbeRequestToken $abe): void
    {
        if ((string) $request->query(AbeRecipientSignature::SIG, '') !== '') {
            return;
        }

        $query = $this->pullPendingAbeClickQuery($request, $abe);
        if ($query === null) {
            return;
        }

        $request->query->replace(array_merge($request->query->all(), $query));
        // Keep input() / validate() in sync for GET requests.
        $request->request->replace(array_merge($request->request->all(), $query));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function pullPendingAbeClickQuery(HttpRequest $request, ProcessAbeRequestToken $abe): ?array
    {
        $ticket = (string) $request->cookie(self::ABE_CLICK_TICKET_COOKIE, '');
        if ($ticket === '') {
            return null;
        }

        $pending = Cache::pull(self::ABE_CLICK_CACHE_PREFIX . $ticket);
        if (!is_array($pending) || ($pending['abe_uuid'] ?? null) !== $abe->uuid) {
            return null;
        }

        $query = $pending['query'] ?? null;
        if (!is_array($query) || $query === []) {
            return null;
        }

        return $query;
    }

    /**
     * Show a screen defined
     * @param int $screenId
     */
    private function showScreen($screenId)
    {
        if (!empty($screenId)) {
            $customScreen = Screen::findOrFail($screenId);
            $manager = app(ScreenBuilderManager::class);
            event(new ScreenBuilderStarting($manager, $customScreen->type ?? 'FORM'));

            return view('processes.screens.completedScreen', compact('customScreen', 'manager'));
        } else {
            return $this->returnErrorResponse(__('Your response has been submitted.'), 200);
        }
    }

    /**
     * Return an ABE result in the format requested by the client.
     * @param string $message
     * @param int $status
     * @param string|null $htmlMessage
     */
    public function returnErrorResponse(string $message, int $status, ?string $htmlMessage = null)
    {
        if (!request()->expectsJson()) {
            return response()->view('tasks.abe-response', [
                'message' => $htmlMessage ?? $message,
            ], $status);
        }

        return response()->json([
            'message' => $message,
        ], $status);
    }
}
