<?php

namespace ProcessMaker\Http\Controllers\Designer;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;

class ProcessController extends Controller
{

    /**
     * Get the list task
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        //return view('processes.tasks.index', ['process' => $process]);
        return view('processes.index');
    }
}
