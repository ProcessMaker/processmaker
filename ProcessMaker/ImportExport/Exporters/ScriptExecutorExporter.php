<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Events\ScriptExecutorUpdated;
use ProcessMaker\Jobs\BuildScriptExecutor;

class ScriptExecutorExporter extends ExporterBase
{
    public static $fallbackMatchColumn = 'title';

    // Do not copy if it exists on the target instance. Only create if it does
    // not exist. Otherwise associate existing on import.
    public static $forceUpdate = true;

    public function export() : void
    {
    }

    public function import() : bool
    {
        switch ($this->mode) {
            case 'copy':
            case 'new':
                BuildScriptExecutor::dispatch($this->model->id, auth()->user()->id);
                break;
            case 'update':
                if (!empty($this->model->getChanges())) {
                    $original = $this->model->getAttributes();
                    ScriptExecutorUpdated::dispatch($this->model->id, $original, $this->model->getChanges());
                    BuildScriptExecutor::dispatch($this->model->id, auth()->user()->id);
                }
                break;

            default:
                // code...
                break;
        }

        return true;
    }
}
