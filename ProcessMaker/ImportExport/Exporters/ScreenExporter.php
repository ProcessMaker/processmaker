<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;

class ScreenExporter extends ExporterBase
{
    public function export() : void
    {
        foreach ($this->model->categories as $category) {
            $this->addDependent('categories', $category, ScreenCategoryExporter::class);
        }

        // Watcher Scripts
        foreach ((array) $this->model->watchers as $watcher) {
            $this->addDependent('scripts', Script::find($watcher['script_id']), ScriptExporter::class);
        }

        // Nested Screens
        foreach ($this->model->nestedScreenIds() as $screenId) {
            $this->addDependent('screens', Screen::find($screenId), self::class);
        }
    }

    public function import() : bool
    {
        $screen = $this->model;

        $categoryIds = [];
        foreach ($this->dependents as $dependent) {
            switch ($dependent->type) {
                case 'categories':
                    $categoryIds = $dependent->model->id;
                    break;
            }
        }
        $screen->screen_category_id = implode(',', $categoryIds);

        return $screen->save();
    }
}
