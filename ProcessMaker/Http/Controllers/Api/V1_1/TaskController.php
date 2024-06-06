<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Http\Resources\Task as Resource;
use ProcessMaker\Http\Resources\V1_1\TaskScreen;
use ProcessMaker\Models\ProcessVersion;

class TaskController extends Controller
{
    protected $defaultFields = [
        'id',
        'element_name',
        'due_at',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = ProcessRequestToken::select($this->defaultFields)
            ->where('element_type', 'task');

        return $query->paginate();
    }

    public function show(ProcessRequestToken $task)
    {
        return $task;
    }

    public function showScreen($taskId)
    {
        $task = ProcessRequestToken::select('id', 'process_request_id', 'element_id', 'process_id')->findOrFail($taskId);
        $response = new TaskScreen($task);
        $response = response($response->toArray(request())['screen'], 200);
        $now = time();
        // screen cache time
        $cacheTime = config('screen_task_cache_time', 86400);
        $response->headers->set('Cache-Control', 'max-age=' . $cacheTime . ', must-revalidate, public');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', $now + $cacheTime) . ' GMT');
        return $response;
    }
}
