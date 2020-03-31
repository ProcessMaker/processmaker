<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
    public function index(Request $request)
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
            'permissions' => [
                'view'   => $request->user()->can('view-script-categories'),
                'create' => $request->user()->can('create-script-categories'),
                'edit'   => $request->user()->can('edit-script-categories'),
                'delete' => $request->user()->can('delete-script-categories'),
            ],
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

    public function builder(Request $request, Script $script)
    {
        $scriptFormat = $script->language_name;
        $processRequestAttributes = $this->getProcessRequestAttributes();
        $processRequestAttributes['user_id'] = $request->user()->id;
        
        $testData = [
            '_request' => $processRequestAttributes
        ];
        return view('processes.scripts.builder', compact('script', 'scriptFormat', 'testData'));
    }

    private function getProcessRequestAttributes()
    {
        $emptyProcessRequest = new ProcessRequest();
        $columns = Schema::connection(
            $emptyProcessRequest->getConnectionName()
        )->getColumnListing(
            $emptyProcessRequest->getTable()
        );

        $attributes = [];

        foreach($columns as $column) {
            $attributes[$column] = null;
        }

        return $attributes;
    }
}
