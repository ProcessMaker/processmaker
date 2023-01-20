<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Arr;
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

    /**
     * If the screen was seeded with a key attribute, we want to associate it
     * using key instead of uuid since the uuid will be different but is
     * essentially refering to the same asset on the target instance.
     */
    public static function modelFinder($uuid, $assetInfo)
    {
        $key = Arr::get($assetInfo, 'attributes.key');
        if (!empty($key)) {
            return Screen::where('key', $key);
        }

        return parent::modelFinder($uuid, $assetInfo);
    }

    /**
     * If we associated it using the key above, then we don't actually want to
     * update the UUID so we remove it from the attrs during import.
     */
    public static function prepareAttributes($attrs)
    {
        $attrs = parent::prepareAttributes($attrs);
        $key = Arr::get($attrs, 'key');
        if (!empty($key)) {
            // Do not overwrite existing UUID
            unset($attrs['uuid']);
        }

        return $attrs;
    }

    public function export() : void
    {
        $this->exportCategories();

        // Script Watchers. Data source watchers are are handled in the data-sources package.
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

        $config = $this->model->config;
        $watchers = $this->model->watchers;

        foreach ($this->dependents as $dependent) {
            switch ($dependent->type) {
                case DependentType::SCREENS:
                    $this->associateNestedScreen($dependent, $config);
                    break;
                case DependentType::SCRIPTS:
                    $this->associateWatchers(self::WATCHER_TYPE_SCRIPT, $dependent, $watchers);
                    break;
            }
        }

        $this->associateCategories(ScreenCategory::class, 'screen_category_id');
        $screen->config = $config;
        $screen->watchers = $watchers;

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
            $screens[] = Screen::findOrFail($screen[1]);
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

    private function associateNestedScreen($dependent, &$config) : void
    {
        $originalId = $dependent->originalId;
        $newId = $dependent->model->id;
        foreach ($config as $pageKey => $page) {
            foreach (Arr::get($page, 'items', []) as $itemKey => $item) {
                if (Arr::get($item, 'component') === 'FormNestedScreen') {
                    if (Arr::get($item, 'config.screen') === $originalId) {
                        Arr::set($config, "$pageKey.items.$itemKey.config.screen", $newId);
                    }
                }
            }
        }
    }

    private function associateWatchers($type, $dependent, &$watchers) : void
    {
        $originalId = $dependent->originalId;
        $newId = $dependent->model->id;

        foreach ($watchers as $key => $watcher) {
            if (Arr::get($watchers, "$key.script.id") === $type . '-' . $originalId) {
                Arr::set($watchers, "$key.script.id", $type . '-' . $newId);
                Arr::set($watchers, "$key.script_id", $newId);
            }
        }
    }
}
