<?php

namespace ProcessMaker\ImportExport\Exporters;

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
    }

    public function import() : bool
    {
        $this->model->value = $this->getReference(DependentType::ENVIRONMENT_VARIABLE_VALUE);

        return $this->model->save();
    }
}
