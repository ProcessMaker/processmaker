<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;

class ScreenExporter extends ExporterBase
{
    const WATCHER_TYPE_SCRIPT = 'script';

    const WATCHER_TYPE_DATA_SOURCE = 'data_source';

    public function export() : void
    {
        foreach ($this->model->categories as $category) {
            $this->addDependent(DependentType::CATEGORIES, $category, ScreenCategoryExporter::class);
        }

        // Watcher Scripts
        foreach ((array) $this->model->watchers as $watcher) {
            if ($this->watcherType($watcher) === self::WATCHER_TYPE_SCRIPT) {
                $this->addDependent(DependentType::SCRIPTS, Script::find($watcher['script_id']), ScriptExporter::class);
            }
        }

        // Nested Screens
        foreach ($this->getNestedScreens() as $screen) {
            $this->addDependent(DependentType::SCREENS, $screen, self::class);
        }
    }

    public function import() : bool
    {
        $screen = $this->model;
        $screen->config = json_decode($this->model->config, true);

        $categoryIds = [];
        $config = $this->model->config;

        foreach ($this->dependents as $dependent) {
            switch ($dependent->type) {
                case DependentType::CATEGORIES:
                    $categoryIds[] = $dependent->model->id;
                    break;
                case DependentType::SCREENS:
                    $this->associateNestedScreen($dependent, $config);
                    break;
            }
        }
        $screen->screen_category_id = implode(',', $categoryIds);
        $screen->config = $config;

        return $screen->saveOrFail();
    }

    public function handleDuplicateAttributes() : array
    {
        return [
            'title' => fn ($title) => $this->incrementString($title),
        ];
    }

    private function getNestedScreens() : array
    {
        $screens = [];
        $screenFinder = new ScreensInScreen();
        foreach ($screenFinder->referencesToExport($this->model, [], null, false) as $screen) {
            $screens[] = Screen::findOrFail($screen[1]);
        }

        return $screens;
    }

    private function watcherType($watcher) : string
    {
        $id = Arr::get($watcher, 'script.id');
        if (substr($id, 0, 11) === self::WATCHER_TYPE_DATA_SOURCE) {
            return self::WATCHER_TYPE_DATA_SOURCE;
        } elseif (substr($id, 0, 6) === self::WATCHER_TYPE_SCRIPT) {
            return self::WATCHER_TYPE_SCRIPT;
        }

        return null;
    }

    private function associateNestedScreen($dependent, &$config) : void
    {
        $id = $dependent->model->id;
        foreach ($config as $pageKey => $page) {
            foreach (Arr::get($page, 'items', []) as $itemKey => $item) {
                if (Arr::get($item, 'component') === 'FormNestedScreen') {
                    if (Arr::get($item, 'config.screen') === $id) {
                        Arr::set($config, "$pageKey.items.$itemKey.config.screen", $id);
                    }
                }
            }
        }
    }
}
