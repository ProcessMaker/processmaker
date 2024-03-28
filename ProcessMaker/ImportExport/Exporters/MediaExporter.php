<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Media;

class MediaExporter extends ExporterBase
{
    public static $fallbackMatchColumn = 'name';

    public $handleDuplicatesByIncrementing = ['name'];

    public function export(): void
    {
        // No dependencies
    }

    public function import(): bool
    {
        return $this->model->save();
    }
}
