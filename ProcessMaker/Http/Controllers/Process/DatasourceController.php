<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\DataSource as Datasource;
use ProcessMaker\Models\DataSourceCategory;

class DatasourceController extends Controller
{
    /**
     * Get the list of datasource
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
        $showCategoriesTab = $countCategories === 0;

        return view('processes.datasource.index', compact('countCategories', 'route', 'permissions', 'location', 'createCategories', 'include', 'labelCount', 'count', 'showCategoriesTab'));
    }

    /**
     * Get page edit
     *
     * @param Datasource $datasource
     *
     * @return Factory|View
     */
    public function edit(Datasource $datasource)
    {
        return view('processes.datasource.edit', compact('datasource'));
    }
}
