<?php

namespace ProcessMaker\ImportExport\Exporters;

class ScriptExecutorExporter extends ExporterBase
{
    public static $fallbackMatchColumn = 'title';

    public $discard = true;

    public function export() : void
    {
    }

    public function import() : bool
    {
        return true;
    }
}
