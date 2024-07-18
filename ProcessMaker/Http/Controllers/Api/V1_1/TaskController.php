<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\V1_1\TaskInterstitialResource;
use ProcessMaker\Http\Resources\V1_1\TaskResource;
use ProcessMaker\Http\Resources\V1_1\TaskScreen;
use ProcessMaker\Models\ProcessRequestToken;

class TaskController extends Controller
{
    protected $defaultFields = [
        'id',
        'element_id',
        'element_name',
        'element_type',
        'status',
        'due_at',
        'user_id',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = ProcessRequestToken::select($this->defaultFields)
            ->where('element_type', 'task');

        $this->processFilters(request(), $query);
        $pagination = $query->paginate();
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
            $query->where('user_id', request()->get('user_id'));
        }

        if ($request->has('process_request_id')) {
            $query->where('process_request_id', $request->input('process_request_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
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
            )->findOrFail($taskId);
        $response = new TaskScreen($task);
        $response = response($response->toArray(request())['screen'], 200);
        $now = time();
        // screen cache time
        $cacheTime = config('screen_task_cache_time', 86400);
        $response->headers->set('Cache-Control', 'max-age=' . $cacheTime . ', must-revalidate, public');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', $now + $cacheTime) . ' GMT');
        return $response;
    }

    public function showInterstitial($taskId)
    {
        $task = ProcessRequestToken::select(
            array_merge($this->defaultFields, ['process_request_id', 'process_id'])
        )->findOrFail($taskId);
        $response = new TaskInterstitialResource($task);
        $response = response($response->toArray(request())['screen'], 200);
        $now = time();
        // screen cache time
        $cacheTime = config('screen_task_cache_time', 86400);
        $response->headers->set('Cache-Control', 'max-age=' . $cacheTime . ', must-revalidate, public');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', $now + $cacheTime) . ' GMT');
        return $response;
    }
}
