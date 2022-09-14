<?php

namespace ProcessMaker\ImportExport\Exporters;

class ScreenExporter extends ExporterBase
{
    public function export() : void
    {
        foreach ($this->model->categories as $category) {
            $this->addDependent('categories', $category, ScreenCategoryExporter::class);
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
