<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Screen;

class CategoryExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['name'];

    public $hidden = true;

    public static $fallbackMatchColumn = 'name';

    public function export() : void
    {
        // Screen Categories have no dependents
    }

    public function import() : bool
    {
        return $this->model->save();
    }
}
