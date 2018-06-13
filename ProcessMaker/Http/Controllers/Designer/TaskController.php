<?php

namespace ProcessMaker\Http\Controllers\Designer;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;

class TaskController extends Controller
{

    /**
     * Get the list task
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Process $process)
    {
        return view('processes.tasks.index', ['process' => $process]);
    }
}
