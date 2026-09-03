<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Enums\ExporterMap;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\Manifest;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\ImportExport\Psudomodels\Psudomodel;

class EnvironmentVariableExporter extends ExporterBase
{
    public $forcePasswordProtect = true;

    public static $fallbackMatchColumn = 'name';

    public $handleDuplicatesByIncrementing = ['name'];

    public $incrementStringSeparator = '_';

    public function __construct(
        Model|Psudomodel|null $model,
        Manifest $manifest,
        Options $options,
        $ignoreExplicitDiscard
    ) {
        parent::__construct($model, $manifest, $options, $ignoreExplicitDiscard);

        // PHP does not allow closures as property defaults.
        $this->discard = fn ($envVar) => (bool) ($envVar->do_not_update ?? false);
    }

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
            if ($asset && $asset->exists) {
                $this->model->asset_type = get_class($asset);
                $this->model->value = (string) $asset->id;

                return $this->model->save();
            }
        }

        if ($this->model->asset_type) {
            // Linked in source, but asset was not imported and was not found on target.
            $this->logger?->addWarning(__(
                'Asset linked to environment variable ":env_variable" was missing on import; link and value were cleared',
                ['env_variable' => $this->model->name]
            ));
            $this->model->asset_type = null;
            $this->model->value = '';

            return $this->model->save();
        }

        // Standard case (non-linked) env var: restore secret/value from export.
        $this->model->value = $this->getReference(DependentType::ENVIRONMENT_VARIABLE_VALUE);

        return $this->model->save();
    }
}
