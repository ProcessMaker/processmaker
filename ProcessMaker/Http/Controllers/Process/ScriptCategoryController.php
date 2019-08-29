<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ScriptCategory;

class ScriptCategoryController extends Controller
{
    /**
     * Get list of Script Categories
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $title = __('Script Categories');
        $btnCreate = __('Category');
        $titleMenu = __('Scripts');
        $routeMenu = 'scripts.index';
        $titleModal = __('Create Category');
        $fieldName = __('Category Name');
        $distinctName = __('The category name must be distinct.');
        $permissions = Auth::user()->hasPermissionsFor('categories');
        $route = 'script_categories';
        $location = '/designer/scripts/categories';
        $create = 'create-categories';
        $include = 'scriptsCount';
        $labelCount = __('# Scripts');
        $count = 'scripts_count';

        return view('categories.index', compact('title', 'btnCreate', 'titleMenu', 'routeMenu', 'permissions', 'titleModal', 'fieldName', 'distinctName', 'route', 'location', 'create', 'include', 'labelCount', 'count'));
    }

    /**
     * Get a specific script category
     *
     * @param ScriptCategory $scriptCategory
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(ScriptCategory $scriptCategory)
    {
        $category = $scriptCategory;
        $titleMenu = __('Categories');
        $routeMenu = 'script-categories.index';
        $route = 'script_categories';
        $location = '/designer/scripts/categories';
        return view('categories.edit', compact('category', 'route', 'location', 'titleMenu', 'routeMenu'));
    }
}
