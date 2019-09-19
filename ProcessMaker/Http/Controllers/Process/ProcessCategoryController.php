<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessCategory;

class ProcessCategoryController extends Controller
{
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
        return view('categories.edit', compact('category', 'route', 'location', 'titleMenu', 'routeMenu'));
    }
}
