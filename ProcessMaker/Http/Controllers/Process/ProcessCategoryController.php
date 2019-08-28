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
        $titleMenu = __('Categories');
        $titleModal = __('Create Category');
        $fieldName = __('Category Name');
        $distinctName = __('The category name must be distinct.');
        $permissions = Auth::user()->hasPermissionsFor('categories');
        $route = 'process_categories';
        $location = '/designer/processes/categories';
        $create = 'create-categories';


        return view('processes.categories.index', compact('title', 'btnCreate', 'titleMenu', 'permissions', 'titleModal', 'fieldName', 'distinctName', 'route', 'location', 'create'));
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
        return view('processes.categories.edit', compact('processCategory'));
    }
}
