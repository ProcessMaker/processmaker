<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ScriptCategory;

class ScriptCategoryController extends Controller
{

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
