<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;

class ScreenExporter extends ExporterBase
{
    public function export() : void
    {
        foreach ($this->model->categories as $category) {
            $this->addDependent(DependentType::CATEGORIES, $category, ScreenCategoryExporter::class);
        }

        // Watcher Scripts
        foreach ((array) $this->model->watchers as $watcher) {
            $this->addDependent(DependentType::SCRIPTS, Script::find($watcher['script_id']), ScriptExporter::class);
        }

        // Nested Screens
        foreach ($this->model->nestedScreenIds() as $screenId) {
            $this->addDependent(DependentType::SCREENS, Screen::find($screenId), self::class);
        }
    }

    public function import() : bool
    {
        $screen = $this->model;

        $categoryIds = [];
        foreach ($this->dependents as $dependent) {
            switch ($dependent->type) {
                case DependentType::CATEGORIES:
                    $categoryIds[] = $dependent->model->id;
                    break;
            }
        }
        $screen->screen_category_id = implode(',', $categoryIds);

        return $this->model->save();
    }
}
