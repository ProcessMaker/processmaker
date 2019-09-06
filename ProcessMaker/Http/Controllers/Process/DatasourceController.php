<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\DataSource;
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
        return view('processes.datasource.index', compact('datasourceCategories'));
    }

    /**
     * Get page edit
     *
     * @param DataSource $datasource
     *
     * @return Factory|View
     */
    public function edit(DataSource $datasource)
    {
        return view('processes.datasource.edit', compact('datasource'));
    }
}
