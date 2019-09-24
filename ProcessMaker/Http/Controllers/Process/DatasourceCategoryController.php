<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\DataSourceCategory;

class DatasourceCategoryController extends Controller
{

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
        $categoriesLabel = __('Categories');
        $itemsLabel = __('Data Sources');
        $categoriesRoute = 'datasource-categories.index';
        $route = 'datasource_categories';
        $location = '/designer/datasources/categories';
        $itemsRoute = 'datasources.index';
        return view('categories.edit', compact('category', 'route', 'location', 'categoriesLabel', 'categoriesRoute', 'itemsRoute', 'itemsLabel'));
    }
}
