<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Screen;

class ScreenCategoryExporter extends ExporterBase
{
    public function export() : void
    {
        // Screen Categories have no dependents
    }

    public function import() : bool
    {
        return $this->model->save();
    }

    public function handleDuplicateAttributes() : array
    {
        return [
            'name' => fn ($name) => $this->incrementString($name),
        ];
    }
}
