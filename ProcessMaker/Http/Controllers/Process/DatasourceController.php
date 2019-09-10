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
        $datasourceCategories = DataSourceCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();

        $permissions = Auth::user()->hasPermissionsFor('categories');
        $route = 'datasource_categories';
        $location = '/designer/datasources';
        $createCategories = 'create-categories';
        $include = 'datasourcesCount';
        $labelCount = __('# Datasources');
        $count = 'datasources_count';

        return view('processes.datasource.index', compact('datasourceCategories', 'route', 'permissions', 'location', 'createCategories', 'include', 'labelCount', 'count'));
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

    /**
     * Get page edit configuration
     *
     * @param Datasource $datasource
     *
     * @return Factory|View
     */
    public function configuration(Datasource $datasource)
    {
        return view('processes.datasource.config', compact('datasource'));
    }
}
