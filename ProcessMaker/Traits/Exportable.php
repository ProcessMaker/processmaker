<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Enums\ExporterMap;
use ProcessMaker\Exception\ExporterNotSupported;
use ProcessMaker\ImportExport\Exporter;

trait Exportable
{
    use HasUuids;

    private $_preventSavingDiscardedModel = false;

    public static function bootPreventDiscardedModelsFromSaving()
    {
        static::saving(function ($model) {
            if ($this->_preventSavingDiscardedModel) {
                return false;
            }
        });
    }

    public function preventSavingDiscardedModel()
    {
        $this->_preventSavingDiscardedModel = true;
    }

    public function export()
    {
        $exporterClass = ExporterMap::getExporterClassForModel($this);
        if (!$exporterClass) {
            throw new ExporterNotSupported();
        }

        $exporter = new Exporter();
        $exporter->export($this, $exporterClass);

        return $exporter->payload();
    }
}
