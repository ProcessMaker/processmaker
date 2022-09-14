<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Screen;

class ScreenCategoryExporter extends ExporterBase
{
    public function export() : void
    {
        // For testing only. Delete later. Attempting circular reference.
        $lastScreen = Screen::orderBy('id', 'desc')->first();
        $this->addDependent('testing', $lastScreen, ScreenExporter::class);
        // End test
    }

    public function import() : bool
    {
        return true;
    }
}
