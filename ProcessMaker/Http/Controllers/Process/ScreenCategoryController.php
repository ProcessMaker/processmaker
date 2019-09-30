<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ScreenCategory;

class ScreenCategoryController extends Controller
{
    /**
     * Get a specific screen category
     *
     * @param ScreenCategory $screenCategory
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(ScreenCategory $screenCategory)
    {
        $category = $screenCategory;
        $categoriesLabel = __('Categories');
        $itemsLabel = __('Screens');
        $categoriesRoute = 'screen-categories.index';
        $route = 'screen_categories';
        $location = '/designer/screens/categories';
        $itemsRoute = 'screens.index';
        return view('categories.edit', compact('category', 'route', 'location', 'categoriesLabel', 'categoriesRoute', 'itemsRoute', 'itemsLabel'));
    }
}
