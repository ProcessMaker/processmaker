<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequestToken;

class TaskController extends Controller
{
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
        return view('tasks.edit', ['task' => $task]);
    }
}
