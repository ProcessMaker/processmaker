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
        $title = __('Datasource Categories');
        $btnCreate = __('Category');
        $titleMenu = __('Datasources');
        $routeMenu = 'datasources.index';
        $titleModal = __('Create Datasource Category');
        $fieldName = __('Category Datasource Name');
        $distinctName = __('The category name must be distinct.');
        $permissions = Auth::user()->hasPermissionsFor('categories');
        $route = 'datasource_categories';
        $location = '/designer/datasources/categories';
        $create = 'create-categories';
        $include = 'datasourcesCount';
        $labelCount = __('# Datasources');
        $count = 'datasources_count';

        return view('categories.index', compact('title', 'btnCreate', 'titleMenu', 'routeMenu', 'permissions', 'titleModal', 'fieldName', 'distinctName', 'route', 'location', 'create', 'include', 'labelCount', 'count'));
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
        $location = '/designer/datasources';
        return view('categories.edit', compact('category', 'route', 'location', 'titleMenu', 'routeMenu'));
    }
}
