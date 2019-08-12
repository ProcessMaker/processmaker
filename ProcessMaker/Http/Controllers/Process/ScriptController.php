<?php

namespace ProcessMaker\Http\Controllers\Process;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Models\ProcessCategory;

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
        $categories = ProcessCategory::where(['status' => 'ACTIVE', 'is_system' => false])
            ->pluck('name' , 'id')->toArray();

        return view('processes.scripts.index', compact('users', 'scriptFormats', 'categories'));
    }

    public function edit(Script $script, User $users)
    {
        $users = User::all();
        $scriptFormats = Script::scriptFormatList();

        $categories = ProcessCategory::where(['status' => 'ACTIVE', 'is_system' => false])
            ->pluck('name' , 'id')->toArray();

        return view('processes.scripts.edit', compact('script', 'users', 'scriptFormats', 'categories'));
    }

    public function builder(Script $script)
    {
        $scriptFormat = $script->language_name;
        
        return view('processes.scripts.builder', compact('script', 'scriptFormat'));
    }
}
