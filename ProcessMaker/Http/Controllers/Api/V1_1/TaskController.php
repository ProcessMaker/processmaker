<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\TaskCollection;
use ProcessMaker\Http\Resources\V1_1\TaskInterstitialResource;
use ProcessMaker\Http\Resources\V1_1\TaskResource;
use ProcessMaker\Http\Resources\V1_1\TaskScreen;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\ProcessTranslations\TranslationManager;
use ProcessMaker\Traits\TaskControllerIndexMethods;

class TaskController extends Controller
{
    use TaskControllerIndexMethods;

    public $doNotSanitize = [
        'data',
        'pmql',
    ];

    protected $defaultFields = [
        'id',
        'element_id',
        'element_name',
        'element_type',
        'status',
        'due_at',
        'user_id',
        'process_request_id',
    ];

    public function indexOptimized(Request $request, $getTotal = false, ?User $user = null)
    {
        if (!$user) {
            $user = Auth::user();
        }

        $request->merge(['optimized' => true]);

        $query = $this->indexOptimizedBaseQuery($request);
        $this->applyIndexFieldSelection($query, $request);
        $this->applyFilters($query, $request);
        $this->excludeNonVisibleTasks($query, $request);
        $this->applyColumnOrdering($query, $request);
        $this->applyStatusFilter($query, $request);

        if ($request->input('processesIManage') === 'true') {
            $this->applyProcessManager($query, $user, $request);
        } else {
            $this->applyForCurrentUser($query, $user);
        }

        $this->applyPmql($query, $request, $user);
        $this->applyAdvancedFilter($query, $request);
        $query->overdue($request->input('overdue'));

        if ($getTotal === true) {
            return $query->count();
        }

        try {
            $response = $query->paginate($request->input('per_page', 10));
        } catch (QueryException $e) {
            return $this->handleQueryException($e);
        }

        $response = $this->applyUserFilter($response, $request, $user);

        if ($response->total() > 0 && $request->input('processesIManage') === 'true') {
            $this->enableUserManager($user);
        }

        $inOverdueQuery = ProcessRequestToken::query()
            ->whereIn('id', $response->pluck('id'))
            ->where('due_at', '<', Carbon::now());

        $response->inOverdue = $inOverdueQuery->count();

        return new TaskCollection($response);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = ProcessRequestToken::select($this->defaultFields)
            ->where('element_type', 'task');

        $this->processFilters(request(), $query);

        $pagination = $query->paginate(request()->get('per_page', 10));
        $perPage = $pagination->perPage();
        $page = $pagination->currentPage();
        $lastPage = $pagination->lastPage();

        return [
            'data' => $pagination->items(),
            'meta' => [
                'total' => $pagination->total(),
                'perPage' => $pagination->perPage(),
                'currentPage' => $pagination->currentPage(),
                'lastPage' => $pagination->lastPage(),
                'count' => $pagination->count(),
                'from' => $perPage * ($page - 1) + 1,
                'last_page' => $lastPage,
                'path' => '/',
                'per_page' => $perPage,
                'to' => $perPage * ($page - 1) + $perPage,
                'total_pages' => ceil($pagination->count() / $perPage),
            ],
        ];
    }

    private function processFilters(Request $request, Builder $query)
    {
        if (request()->has('user_id')) {
            ProcessRequestToken::scopeWhereUserAssigned($query, request()->get('user_id'));
        }

        if ($request->has('process_request_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('process_request_id', $request->input('process_request_id'));
                $this->addSubprocessTasks($request, $q);
            });
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
    }

    private function addSubprocessTasks(Request $request, Builder &$q)
    {
        if ($request->has('include_sub_tasks')) {
            $q->orWhereIn(
                'process_request_id',
                ProcessRequest::select('id')
                    ->where('parent_request_id', $request->input('process_request_id'))
            );
        }
    }

    public function show(ProcessRequestToken $task)
    {
        $resource = TaskResource::preprocessInclude(request(), ProcessRequestToken::where('id', $task->id));

        return $resource->toArray(request());
    }

    public function showScreen($taskId)
    {
        $task = ProcessRequestToken::select(
            array_merge($this->defaultFields, ['process_request_id', 'process_id'])
        )
        ->with([
            'processRequest' => function ($query) {
                $query->select('id', 'process_version_id');
            },
        ])->findOrFail($taskId);

        // Get screen version and prepare cache key
        $processId = $task->process_id;
        $processVersionId = $task->processRequest->process_version_id;
        $language = TranslationManager::getTargetLanguage();
        $screenVersion = $task->getScreenVersion();

        // Get the appropriate cache handler based on configuration

        $screenCache = ScreenCacheFactory::getScreenCache();
        // Create cache key
        $key = $screenCache->createKey([
            'process_id' => (int) $processId,
            'process_version_id' => (int) $processVersionId,
            'language' => $language,
            'screen_id' => (int) $screenVersion->screen_id,
            'screen_version_id' => (int) $screenVersion->id,
        ]);

        // Try to get the screen from cache
        $translatedScreen = $screenCache->get($key);

        if ($translatedScreen === null) {
            // If not in cache, create new response
            $response = new TaskScreen($task);
            $translatedScreen = $response->toArray(request())['screen'];

            // Store in cache
            $screenCache->set($key, $translatedScreen);
        }

        return response($translatedScreen, 200);
    }

    public function showInterstitial($taskId)
    {
        $task = ProcessRequestToken::select(
            array_merge($this->defaultFields, ['process_request_id', 'process_id'])
        )->findOrFail($taskId);
        $response = new TaskInterstitialResource($task);
        $response = response($response->toArray(request())['screen'], 200);

        return $response;
    }

    private function handleQueryException(QueryException $e)
    {
        $regex = '~Column not found: 1054 Unknown column \'(.*?)\' in \'where clause\'~';

        preg_match($regex, $e->getMessage(), $m);

        $message = __('PMQL Is Invalid.');

        if (count($m) > 1) {
            $message .= ' ' . __('Column not found: ') . '"' . $m[1] . '"';
        }

        \Log::error($e->getMessage());

        return response([
            'message' => $message,
        ], 422);
    }
}
