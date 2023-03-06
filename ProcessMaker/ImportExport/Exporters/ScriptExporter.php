<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\ScriptCategory;

class ScriptExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['title'];

    public static $fallbackMatchColumn = 'title';

    public function export() : void
    {
        $this->exportCategories();

        foreach ($this->getEnvironmentVariables() as $environmentVariable) {
            $this->addDependent(DependentType::ENVIRONMENT_VARIABLES, $environmentVariable, EnvironmentVariableExporter::class);
        }

        $this->addDependent('user', $this->model->runAsUser, UserExporter::class);

        $this->addDependent('executor', $this->model->scriptExecutor, ScriptExecutorExporter::class);
    }

    public function import() : bool
    {
        $this->associateCategories(ScriptCategory::class, 'script_category_id');

        foreach ($this->getDependents('user') as $dependent) {
            $scriptUser = $dependent->model;
            $this->model->run_as_user_id = $scriptUser->id;
        }

        foreach ($this->getDependents('executor') as $dependent) {
            $executor = $dependent->model;
            $this->model->script_executor_id = $executor->id;
        }

        return $this->model->save();
    }

    private function getEnvironmentVariables() : array
    {
        $environmentVariables = EnvironmentVariable::get();
        $environmentVariablesFound = [];

        // Search for environment variable present in the code
        foreach ($environmentVariables as $variable) {
            if (strpos($this->model->code, $variable->name)) {
                $environmentVariablesFound[] = $variable;
            }
        }

        return $environmentVariablesFound;
    }
}
