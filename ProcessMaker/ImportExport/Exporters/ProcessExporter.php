<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\Screen;

class ProcessExporter extends ExporterBase
{
    public function export() : void
    {
        $manager = resolve(ExportManager::class);

        // Process Categories.
        foreach ($this->model->categories as $category) {
            $this->addDependent(DependentType::CATEGORIES, $category, ProcessCategoryExporter::class);
        }

        // Screens.
        $screenIds = array_merge(
            [
                $this->model->cancel_screen_id,
                $this->model->request_detail_screen_id,
            ],
            $manager->getDependenciesOfType(Screen::class, $this->model)
        );
        $screens = Screen::findMany($screenIds);
        foreach ($screens as $screen) {
            $this->addDependent(DependentType::SCREENS, $screen, ScreenExporter::class);
        }
    }

    public function import() : bool
    {
        return $this->model->save();
    }
}
