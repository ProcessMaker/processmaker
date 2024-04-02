<?php

namespace ProcessMaker\ImportExport\Exporters;

class ScreenTemplatesExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['name'];

    public function export() : void
    {
    }

    public function import() : bool
    {
        return true;
    }
}
