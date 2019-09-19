<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenType;

class ScreenController extends Controller
{
    /**
     * Get the list of screens
     *
     * @param Request $request
     * @return Factory|View
     */
    public function index()
    {
        $types = [];
        foreach(ScreenType::pluck('name')->toArray() as $type) {
            $types[$type] = __(ucwords(strtolower($type)));
        }
        $countCategories = ScreenCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();

        $title = __('Screen Categories');
        $btnCreate = __('Category');
        $titleMenu = __('Screens');
        $routeMenu = 'screens.index';
        $titleModal = __('Create Screen Category');
        $fieldName = __('Category Screen Name');
        $distinctName = __('The category name must be distinct.');
        $permissions = Auth::user()->hasPermissionsFor('categories');
        $route = 'screen_categories';
        $location = '/designer/screens/categories';
        $create = 'create-categories';
        $include = 'screensCount';
        $labelCount = __('# Screens');
        $count = 'screens_count';
        $showCategoriesTab = 'screen-categories.index' === \Request::route()->getName() || $countCategories === 0 ? true : false;

        return view('processes.screens.index', compact('types', 'countCategories', 'title', 'btnCreate', 'titleMenu',
            'routeMenu', 'permissions', 'titleModal', 'fieldName', 'distinctName', 'route', 'location', 'create',
            'include', 'labelCount', 'count', 'showCategoriesTab'));
    }

    /**
     * Get page edit
     *
     * @param Screen $screen
     *
     * @return Factory|View
     */
    public function edit(Screen $screen)
    {
        return view('processes.screens.edit', compact('screen'));
    }

    /**
     * Get page export
     *
     * @param Screen $screen
     *
     * @return Factory|View
     */
    public function export(Screen $screen)
    {
        return view('processes.screens.export', compact('screen'));
    }

    /**
     * Get page import
     *
     * @param Screen $screen
     *
     * @return Factory|View
     */
    public function import(Screen $screen)
    {
        return view('processes.screens.import');
    }


    /**
     * Download the JSON definition of the screen
     *
     * @param Screen $screen
     * @param string $key
     *
     * @return stream
     */
    public function download(Screen $screen, $key)
    {
        $fileName = snake_case($screen->title) . '.json';
        $fileContents = Cache::get($key);

        if (! $fileContents) {
            return abort(404);
        } else {
            return response()->streamDownload(function () use ($fileContents) {
                echo $fileContents;
            }, $fileName, [
                'Content-type' => 'application/json',
            ]);
        }
    }
}
