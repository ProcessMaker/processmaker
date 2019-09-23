<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
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
        $scriptFormats = Script::scriptFormatList();
        $countCategories = ScriptCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();
        $title = __('Script Categories');
        $titleMenu = __('Scripts');
        $routeMenu = 'scripts.index';
        $titleModal = __('Create Category');
        $fieldName = __('Category Name');
        $distinctName = __('The category name must be distinct.');
        $permissions = Auth::user()->hasPermissionsFor('categories');
        $route = 'script_categories';
        $location = '/designer/scripts/categories';
        $include = 'scriptsCount';
        $labelCount = __('# Scripts');
        $count = 'scripts_count';
        $showCategoriesTab = 'script-categories.index' === \Request::route()->getName() || $countCategories === 0 ? true : false;

        return view('processes.scripts.index', compact('scriptFormats', 'countCategories', 'title', 'titleMenu', 'routeMenu',
            'permissions', 'titleModal', 'fieldName', 'distinctName', 'route', 'location', 'include', 'labelCount', 'count', 'showCategoriesTab'));
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

        return view('processes.scripts.builder', compact('script', 'scriptFormat'));
    }
}
