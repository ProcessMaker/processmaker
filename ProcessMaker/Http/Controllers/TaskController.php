<?php

namespace ProcessMaker\Http\Controllers;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequestToken;

class TaskController extends Controller
{
    private static $dueLabels = [
        'open' => 'Due ',
        'completed' => 'Completed ',
        'overdue' => 'Due ',
    ];

    public function index()
    {
        return view('tasks.index');
    }

    public function show()
    {
        return view('tasks.show');
    }

    public function edit(ProcessRequestToken $task)
    {
        return view('tasks.edit', ['task' => $task, 'dueLabels' => self::$dueLabels]);
    }
}
