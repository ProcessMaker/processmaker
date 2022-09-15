<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Script;

class ScriptExporter extends ExporterBase
{
    public function export() : void
    {
    }

    public function import() : bool
    {
        return true;
    }
}
