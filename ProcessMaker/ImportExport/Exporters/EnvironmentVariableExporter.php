<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Enums\ExporterMap;
use ProcessMaker\ImportExport\DependentType;

class EnvironmentVariableExporter extends ExporterBase
{
    public $forcePasswordProtect = true;

    public static $fallbackMatchColumn = 'name';

    public $handleDuplicatesByIncrementing = ['name'];

    public $incrementStringSeparator = '_';

    public function export() : void
    {
        $this->addReference(DependentType::ENVIRONMENT_VARIABLE_VALUE, $this->model->value);

        if (!$this->model->hasLinkedAsset()) {
            return;
        }

        $asset = $this->model->resolveLinkedAsset();
        if (!$asset) {
            Log::warning('EnvironmentVariableExporter: linked asset not found during export', [
                'environment_variable' => $this->model->name,
                'asset_type' => $this->model->asset_type,
                'value' => $this->model->value,
            ]);

            return;
        }

        $exporterClass = ExporterMap::getExporterClassForModel($asset);
        if (!$exporterClass) {
            Log::warning('EnvironmentVariableExporter: no exporter registered for linked asset', [
                'environment_variable' => $this->model->name,
                'asset_type' => $this->model->asset_type,
            ]);

            return;
        }

        $this->addDependent(DependentType::ENVIRONMENT_VARIABLE_ASSET, $asset, $exporterClass);
    }

    public function import() : bool
    {
        foreach ($this->getDependents(DependentType::ENVIRONMENT_VARIABLE_ASSET, true) as $dependent) {
            $asset = $dependent->model;
            if (!$asset) {
                continue;
            }

            $this->model->asset_type = get_class($asset);
            $this->model->value = (string) $asset->id;

            return $this->model->save();
        }

        // Non-linked env vars (or discarded asset dependents): restore exported value.
        $this->model->value = $this->getReference(DependentType::ENVIRONMENT_VARIABLE_VALUE);

        return $this->model->save();
    }
}
