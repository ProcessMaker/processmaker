<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequestToken;

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
}
