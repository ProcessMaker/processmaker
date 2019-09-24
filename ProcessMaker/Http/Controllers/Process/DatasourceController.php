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
        $catConfig = (object) [
            'labels' => (object) [
                'newCategoryTitle' => __('Create Data Source Category'),
                'countColumn' => __('# Datasources'),
            ],
            'routes' => (object) [
                'itemsIndexWeb' => 'datasources.index',
                'editCategoryWeb' => 'datasource-categories.edit',
                'categoryListApi' => 'api.datasource_categories.index',
            ],
            'countField' => 'datasources_count',
            'apiListInclude' => 'datasourcesCount',
        ];

        $listConfig = (object) [
            'countCategories' => DataSourceCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count()
        ];

        return view('processes.datasource.index', compact ('listConfig', 'catConfig'));
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
