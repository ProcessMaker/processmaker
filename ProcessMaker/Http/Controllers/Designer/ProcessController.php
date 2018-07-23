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
        return view('processes.index');
    }


    /**
     * Redirects to the view of the designer
     *
     * @param Process $process
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Process $process)
    {
        return view('designer.designer', compact('process'));
    }
}
