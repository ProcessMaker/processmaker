<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessCategory;

class ProcessCategoryController extends Controller
{
    /**
     * Get list of Process Categories
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $title = __('Process Categories');
        $btnCreate = __('Category');
        $titleMenu = __('Processes');
        $routeMenu = 'processes.index';
        $titleModal = __('Create Category');
        $fieldName = __('Category Name');
        $distinctName = __('The category name must be distinct.');
        $permissions = Auth::user()->hasPermissionsFor('categories');
        $route = 'process_categories';
        $location = '/designer/processes/categories';
        $create = 'create-categories';
        $include = 'processesCount';
        $labelCount = __('# Processes');
        $count = 'processes_count';


        return view('processes.categories.index', compact('title', 'btnCreate', 'titleMenu', 'routeMenu', 'permissions', 'titleModal', 'fieldName', 'distinctName', 'route', 'location', 'create', 'include', 'labelCount', 'count'));
    }

    /**
     * Get a specific process category
     *
     * @param ProcessCategory $processCategory
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(ProcessCategory $processCategory)
    {
        $category = $processCategory;
        $route = 'process_categories';
        $location = '/designer/processes/categories';
        $titleMenu = __('Categories');
        $routeMenu = 'categories.index';
        return view('processes.categories.edit', compact('category', 'route', 'location', 'titleMenu', 'routeMenu'));
    }
}
