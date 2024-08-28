<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;

class ScreenExporter extends ExporterBase
{
    const WATCHER_TYPE_SCRIPT = 'script';

    const WATCHER_TYPE_DATA_SOURCE = 'data_source';

    public $handleDuplicatesByIncrementing = ['title'];

    public static $fallbackMatchColumn = ['key', 'title'];

    public function export() : void
    {
        $this->exportCategories();

        // Script Watchers. Data source watchers are are handled in the data-sources package.
        foreach ((array) $this->model->watchers as $watcher) {
            if ($this->watcherType($watcher) === self::WATCHER_TYPE_SCRIPT) {
                $id = $watcher['script_id'];
                $script = Script::find($id);
                if ($script) {
                    $this->addDependent(DependentType::SCRIPTS, $script, ScriptExporter::class, $id);
                } else {
                    \Log::debug("ScriptId: $script not exists in watcher " . $watcher['name']);
                }
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
        $watchers = $this->model->watchers;

        $screenIdMap = [];
        foreach ($this->dependents as $dependent) {
            switch ($dependent->type) {
                case DependentType::SCREENS:
                    $screenIdMap[$dependent->originalId] = $dependent->model->id;
                    break;
                case DependentType::SCRIPTS:
                    $originalId = $dependent->meta;
                    $this->associateWatchers(self::WATCHER_TYPE_SCRIPT, $dependent, $watchers, $originalId);
                    break;
            }
        }

        $screen->config = $this->associateNestedScreens($screenIdMap, $screen->config);
        $this->associateCategories(ScreenCategory::class, 'screen_category_id');
        $screen->watchers = $watchers;

        // There should only be one default interstitial screen
        if ($screen->key === 'interstitial') {
            $screen->key = null;
        }

        $success = $screen->saveOrFail();

        return $success;
    }

    protected function getExportAttributes() : array
    {
        $attrs = parent::getExportAttributes();
        unset($attrs['screen_category_id']);

        return $attrs;
    }

    private function getNestedScreens() : array
    {
        $screens = [];
        $screenFinder = new ScreensInScreen();
        foreach ($screenFinder->referencesToExport($this->model, [], null, false) as $screen) {
            try {
                $screen = Screen::find($screen[1]);
                if ($screen) {
                    $screens[] = $screen;
                } else {
                    \Log::debug("NestedScreen screenId: $screen[1] not exists");
                }
            } catch (ModelNotFoundException $error) {
                \Log::error($error->getMessage());
                continue;
            }
        }

        return $screens;
    }

    protected function watcherType($watcher) : string
    {
        $id = Arr::get($watcher, 'script.id');
        if (substr($id, 0, 11) === self::WATCHER_TYPE_DATA_SOURCE) {
            return self::WATCHER_TYPE_DATA_SOURCE;
        } elseif (substr($id, 0, 6) === self::WATCHER_TYPE_SCRIPT) {
            return self::WATCHER_TYPE_SCRIPT;
        }
        throw new \Exception('Bad watcher type');

        return null;
    }

    private function associateNestedScreens($screenIdMap, $config, $recursion = 0)
    {
        if ($recursion > 100) {
            throw new \Exception('Recursion limit exceeded. Screen is self-referencing');
        }

        if (!is_array($config)) {
            return $config;
        }

        foreach ($config as $i => $item) {
            if (Arr::get($item, 'component') === 'FormMultiColumn') {
                foreach ($item['items'] as $mi => $mcItems) {
                    $config[$i]['items'][$mi] = $this->associateNestedScreens($screenIdMap, $mcItems, $recursion + 1);
                }
            } elseif (Arr::has($item, 'items')) {
                // This covers both pages and FormLoops
                $config[$i]['items'] = $this->associateNestedScreens($screenIdMap, $item['items'], $recursion + 1);
            } elseif (Arr::get($item, 'component') === 'FormNestedScreen') {
                $originalId = Arr::get($item, 'config.screen', null);
                if ($originalId) {
                    $newId = Arr::get($screenIdMap, $originalId, null);
                    Arr::set($config, "{$i}.config.screen", $newId);
                }
            }
        }

        return $config;
    }

    private function associateWatchers($type, $dependent, &$watchers, $originalId) : void
    {
        $newId = $dependent->model->id;
        foreach ($watchers as $key => $watcher) {
            if (Arr::get($watchers, "$key.script.id") === $type . '-' . $originalId) {
                Arr::set($watchers, "$key.script.title", $dependent->model->title);
                $watcherType = Arr::get($watchers, "$key.script.id");
                if (Str::contains($watcherType, 'data_source')) {
                    Arr::set($watchers, "$key.script.title", $dependent->model->name);
                }
                Arr::set($watchers, "$key.script.description", $dependent->model->description);
                Arr::set($watchers, "$key.script.id", $type . '-' . $newId);
                Arr::set($watchers, "$key.script_id", strval($newId));
            }
        }
    }
}
