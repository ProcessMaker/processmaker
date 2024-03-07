<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\ScreenType;
use ProcessMaker\Traits\HasControllerAddons;

class ScreenController extends Controller
{
    use HasControllerAddons;

    /**
     * Get the list of screens
     *
     * @param Request $request
     * @return Factory|View
     */
    public function index(Request $request)
    {
        $types = [];
        foreach (ScreenType::pluck('name')->toArray() as $type) {
            $types[$type] = __(ucwords(strtolower($type)));
        }
        asort($types);
        $catConfig = (object) [
            'labels' => (object) [
                'countColumn' => __('# Screens'),
            ],
            'routes' => (object) [
                'itemsIndexWeb' => 'screens.index',
                'editCategoryWeb' => 'screen-categories.edit',
                'categoryListApi' => 'api.screen_categories.index',
            ],
            'countField' => 'screens_count',
            'apiListInclude' => 'screensCount',
            'permissions' => [
                'view' => $request->user()->can('view-screen-categories'),
                'create' => $request->user()->can('create-screen-categories'),
                'edit' => $request->user()->can('edit-screen-categories'),
                'delete' => $request->user()->can('delete-screen-categories'),
            ],
        ];

        $listConfig = (object) [
            'types' => $types,
            'countCategories' => ScreenCategory::where(['status' => 'ACTIVE', 'is_system' => false])->count(),
        ];

        $listScreenTemplates = (object) [
            'myScreenTemplates' => ScreenTemplates::nonSystem()->get(),
            'publicScreenTemplates' => ScreenTemplates::nonSystem()->get(),
        ];

        return view(
            'processes.screens.index',
            compact(
                'listConfig',
                'catConfig',
                'listScreenTemplates',
            )
        );
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
        $addons = $this->getPluginAddons('edit', compact(['screen']));
        $assignedProjects = json_decode($screen->projects, true);

        return view('processes.screens.edit', compact('screen', 'addons', 'assignedProjects'));
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
        $fileName = trim($screen->title) . '.json';
        $fileContents = Cache::get($key);

        if (!$fileContents) {
            return abort(404);
        } else {
            return response()->streamDownload(function () use ($fileContents) {
                echo $fileContents;
            }, $fileName, [
                'Content-type' => 'application/json',
            ]);
        }
    }

    public function preview(Request $request)
    {
        $data = json_decode($request->query('node'), true) ?? [];
        $screen = Screen::find($data['screenRef']);

        $manager = app(ScreenBuilderManager::class);
        event(new ScreenBuilderStarting($manager, $screen->type ?? 'FORM'));

        return view('processes.screens.preview', compact('screen', 'manager'));
    }
}
