<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Arr;
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
        $attrs = $assetInfo['attributes'];
        if (Arr::get($attrs, 'id', '') === '') {
            return [null, 'uuid'];
        }

        $signal = new Signal();
        $signal->fill($assetInfo['attributes']);

        return [$signal, 'uuid'];
    }

    public static function prepareAttributes($attrs)
    {
        // Do not remove the ID attribute, return as is

        return $attrs;
    }
}
