<?php

namespace ProcessMaker\Http\Controllers\Process;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;

class ScriptController extends Controller
{
     /**
     * Get the list of environment variables
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        $users = User::all();
        return view('processes.scripts.index', compact('users'));
    }

    public function edit(Script $script, User $users)
    {
        $users = User::all();
        return view('processes.scripts.edit', compact('script', 'users'));
    }

    public function builder(Script $script)
    {
        return view('processes.scripts.builder', ['script' => $script]);
    }
}
