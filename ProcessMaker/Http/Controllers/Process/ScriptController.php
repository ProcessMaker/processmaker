<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Events\ScriptBuilderStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ScriptBuilderManager;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\PackageHelper;
use ProcessMaker\Traits\HasControllerAddons;

class ScriptController extends Controller
{
    use HasControllerAddons;

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
            'scriptExecutors' => ScriptExecutor::list(),
            'countCategories' => ScriptCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count(),
        ];

        $runAsUserDefault = User::where('is_administrator', true)->first();

        return view('processes.scripts.index', compact('listConfig', 'catConfig', 'runAsUserDefault'));
    }

    public function edit(Script $script, User $users)
    {
        $selectedUser = $script->runAsUser;
        $assignedProjects = json_decode($script->projects, true);
        $scriptExecutors = ScriptExecutor::list($script->scriptExecutor->language, true);
        $addons = $this->getPluginAddons('edit', compact(['script']));

        return view('processes.scripts.edit', compact('script', 'selectedUser', 'scriptExecutors', 'addons', 'assignedProjects'));
    }

    public function builder(ScriptBuilderManager $manager, Request $request, Script $script, $processId = null)
    {
        $processRequestAttributes = $this->getProcessRequestAttributes();
        $processRequestAttributes['user_id'] = $request->user()->id;

        $testData = [
            '_request' => $processRequestAttributes,
        ];

        $draft = $script->versions()->draft()->first();
        if ($draft) {
            $script->fill($draft->only(['code']));
        }

        /**
         * Emit the ScriptBuilderStarting event, passing in our ScriptBuilderManager instance. This will
         * allow packages to add additional javascript for Script Builder initialization which
         * can customize the Script Builder controls list.
         */
        event(new ScriptBuilderStarting($manager));

        return view('processes.scripts.builder', [
            'script' => $script,
            'manager' => $manager,
            'testData' => $testData,
            'autoSaveDelay' => config('versions.delay.script', 5000),
            'isVersionsInstalled' => PackageHelper::isPmPackageVersionsInstalled(),
            'isDraft' => $draft !== null,
            'user' => \Auth::user(),
            'processId' => $processId,
        ]);
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

        foreach ($columns as $column) {
            $attributes[$column] = null;
        }

        return $attributes;
    }

    public function preview(Request $request)
    {
        $data = json_decode($request->query('node'), true) ?? [];
        $script = Script::find($data['scriptRef']);

        $processRequestAttributes = $this->getProcessRequestAttributes();
        $processRequestAttributes['user_id'] = $request->user()->id;

        $testData = [
            '_request' => $processRequestAttributes,
        ];

        $draft = $script->versions()->draft()->first();
        if ($draft) {
            $script->fill($draft->only(['code']));
        }

        $manager = new ScriptBuilderManager();

        return view('processes.scripts.preview', [
            'script' => $script,
            'manager' => $manager,
            'testData' => $testData,
            'autoSaveDelay' => 0,
            'isVersionsInstalled' => false,
            'isDraft' => true,
            'user' => \Auth::user(),
        ]);
    }
}
