<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\DataSourceCategory;

class DatasourceCategoryController extends Controller
{
    /**
     * Get list of Datasource Categories
     *
     * @return Factory|View
     */
    public function index()
    {
        $countCategories = DataSourceCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();

        $permissions = Auth::user()->hasPermissionsFor('categories');
        $route = 'datasource_categories';
        $location = '/designer/datasources/categories';
        $createCategories = 'create-categories';
        $include = 'datasourcesCount';
        $labelCount = __('# Datasources');
        $count = 'datasources_count';
        $showCategoriesTab = true;

        return view('processes.datasource.index', compact('countCategories', 'route', 'permissions', 'location', 'createCategories', 'include', 'labelCount', 'count', 'showCategoriesTab'));
    }

    /**
     * Get a specific datasource category
     *
     * @param DatasourceCategory $datasourceCategory
     *
     * @return Factory|View
     */
    public function edit(DataSourceCategory $datasourceCategory)
    {
        $category = $datasourceCategory;
        $titleMenu = __('Categories');
        $routeMenu = 'datasource-categories.index';
        $route = 'datasource_categories';
        $location = '/designer/datasources/categories';
        return view('categories.edit', compact('category', 'route', 'location', 'titleMenu', 'routeMenu'));
    }
}
