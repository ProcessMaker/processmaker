<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\Psudomodels\Signal;
use ProcessMaker\ImportExport\SignalQuery;

class SignalExporter extends ExporterBase
{
    public function export() : void
    {
    }

    public function import() : bool
    {
        return true;
    }

    public function getDescription()
    {
        return $this->model->detail;
    }

    public static function modelFinder($uuid, $assetInfo)
    {
        return new SignalQuery($assetInfo);
    }

    public static function prepareAttributes($attrs)
    {
        // Do not remove the ID attribute, return as is

        return $attrs;
    }
}
