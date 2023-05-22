<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Psudomodels\Signal;
use ProcessMaker\ImportExport\SignalHelper;
use ProcessMaker\ImportExport\SignalQuery;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\SignalData;

class SignalExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['id'];

    public $incrementStringSeparator = '_';

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

    protected function duplicateExists($attribute, $value) : bool
    {
        if ($attribute !== 'id') {
            return false;
        }

        // Not needed for update imports
        if ($this->mode === 'update') {
            return false;
        }

        $signalHelper = app()->make(SignalHelper::class);

        if ($this->model->global) {
            $globalSignals = $signalHelper->getGlobalSignals();

            return $globalSignals->has($value);
        }

        $allSignals = $signalHelper->getAllSignals();

        return $allSignals->pluck('id')->contains($value);
    }
}
