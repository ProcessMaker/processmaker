<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenType;
use ProcessMaker\Models\Script;

class ScreenController extends Controller
{
    /**
     * Get the list of screens
     *
     * @return Factory|View
     */
    public function index()
    {
        $types = [];
        foreach(ScreenType::pluck('name')->toArray() as $type) {
            $types[$type] = __(ucwords(strtolower($type)));
        }
        $screenCategories = ScreenCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count();
        return view('processes.screens.index', compact('types', 'screenCategories'));
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
