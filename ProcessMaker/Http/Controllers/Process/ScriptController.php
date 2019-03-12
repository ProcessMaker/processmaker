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
        $scriptFormats = Script::scriptFormatList();

        return view('processes.scripts.index', compact('users', 'scriptFormats'));
    }

    public function edit(Script $script, User $users)
    {
        $users = User::all();
        $scriptFormats = Script::scriptFormatList();
        
        return view('processes.scripts.edit', compact('script', 'users', 'scriptFormats'));
    }

    public function builder(Script $script)
    {
        $scriptFormat = $script->language_name;
        
        return view('processes.scripts.builder', compact('script', 'scriptFormat'));
    }
}
