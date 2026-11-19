<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Events\ScriptExecutorUpdated;
use ProcessMaker\Jobs\BuildScriptExecutor;
use ProcessMaker\Jobs\MoveScriptExecutorToMicroservice;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;

class ScriptExecutorExporter extends ExporterBase
{
    public static $fallbackMatchColumn = 'title';

    // Do not copy if it exists on the target instance. Only create if it does
    // not exist. Otherwise associate existing on import.
    public static $forceUpdate = true;

    // Type is managed by the target instance (Base vs Custom). Importing a
    // process package must not flip a Base executor to Custom.
    public static $doNotUpdateAttributes = ['type'];

    public function export() : void
    {
    }

    public function import() : bool
    {
        $authenticatedUser = Auth::user();
        $userId = $authenticatedUser ? $authenticatedUser->id : User::where('is_administrator', true)->first()->id;
        switch ($this->mode) {
            case 'copy':
            case 'new':
                // afterCommit is needed because we are in a db transaction
                if (!config('script-runner-microservice.enabled')) {
                    BuildScriptExecutor::dispatch($this->model->id, $userId)->afterCommit();
                } else {
                    MoveScriptExecutorToMicroservice::dispatch($this->model->uuid)->afterCommit();
                }
                break;
            case 'update':
                // getChanges() survives refresh() after save, so description/config
                // updates still reach this branch during process import.
                if (!empty($this->model->getChanges())) {
                    $original = $this->model->getAttributes();
                    ScriptExecutorUpdated::dispatch($this->model->id, $original, $this->model->getChanges());
                    // afterCommit is needed because we are in a db transaction
                    if (!config('script-runner-microservice.enabled')) {
                        BuildScriptExecutor::dispatch($this->model->id, $userId)->afterCommit();
                    } elseif ($this->model->type === ScriptExecutorType::Custom) {
                        // Base/system executors must stay on the shared language runner.
                        // Transitioning them here would set type=Custom and break runs.
                        MoveScriptExecutorToMicroservice::dispatch($this->model->uuid)->afterCommit();
                    }
                }
                break;

            default:
                // code...
                break;
        }

        return true;
    }
}
