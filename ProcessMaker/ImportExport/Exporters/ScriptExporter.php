<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Collection;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\EnvironmentVariable;

class ScriptExporter extends ExporterBase
{
    public function export() : void
    {
        foreach ($this->model->categories as $category) {
            $this->addDependent(DependentType::CATEGORIES, $category, ScriptCategoryExporter::class);
        }

        foreach ($this->getEnvironmentVariables() as $variable) {
            $this->addDependent(DependentType::ENVIRONMENT_VARIABLES, $variable, EnvironmentVariableExporter::class);
        }
    }

    public function import() : bool
    {
        return $this->model->save();
    }

    private function getEnvironmentVariables(): Collection
    {
        return EnvironmentVariable::query()
            ->whereRaw("LOCATE(name, '{$this->model->code}') > 0")
            ->get();
    }
}
