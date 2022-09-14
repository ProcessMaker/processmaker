<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Screen;

class ScreenCategoryExporter extends ExporterBase
{
    public function export() : void
    {
    }

    public function import() : bool
    {
        return true;
    }
}
