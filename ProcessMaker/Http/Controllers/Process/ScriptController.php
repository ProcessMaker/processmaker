<?php

namespace ProcessMaker\Http\Controllers\Process;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Script;

class ScriptController extends Controller
{
     /**
     * Get the list of environment variables
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        return view('processes.scripts.index');
    }

    public function show()
    {
        return view('processes.scripts.edit');
    }

    public function edit(Script $script)
    {
        return view('processes.scripts.edit', ['script' => $script]);
    }
}
