<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
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
        $catConfig = (object) [
            'labels' => (object) [
                'countColumn' => __('# Scripts'),
            ],
            'routes' => (object) [
                'itemsIndexWeb' => 'scripts.index',
                'editCategoryWeb' => 'script-categories.edit',
                'categoryListApi' => 'api.script_categories.index',
            ],
            'countField' => 'scripts_count',
            'apiListInclude' => 'scriptsCount',
        ];

        $listConfig = (object) [
            'scriptFormats' => Script::scriptFormatList(),
            'countCategories' => ScriptCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count()
        ];

        return view('processes.scripts.index', compact ('listConfig', 'catConfig'));
    }

    public function edit(Script $script, User $users)
    {
        $selectedUser = $script->runAsUser;
        $scriptFormats = Script::scriptFormatList();

        return view('processes.scripts.edit', compact('script', 'selectedUser', 'scriptFormats'));
    }

    public function builder(Script $script)
    {
        $scriptFormat = $script->language_name;
        $testData = [
            '_request' => factory(ProcessRequest::class)->raw([
                'callable_id' => 'process_1',
                'user_id' => Auth::id(),
                'process_id' => 1,
                'process_collaboration_id' => 1,
            ]),
        ];

        return view('processes.scripts.builder', compact('script', 'scriptFormat', 'testData'));
    }
}
