<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\ScriptExecutorUpdated;
use ProcessMaker\Jobs\BuildScriptExecutor;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;

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
        $authenticatedUser = Auth::user();
        $userId = $authenticatedUser ? $authenticatedUser->id : User::where('is_administrator', true)->first()->id;
        switch ($this->mode) {
            case 'copy':
            case 'new':
                // afterCommit is needed because we are in a db transaction
                BuildScriptExecutor::dispatch($this->model->id, $userId)->afterCommit();
                break;
            case 'update':
                if (!empty($this->model->getChanges())) {
                    $original = $this->model->getAttributes();
                    ScriptExecutorUpdated::dispatch($this->model->id, $original, $this->model->getChanges());
                    if (!app()->runningInConsole()) {
                        $user = Auth::user();
                    } else {
                        $user = User::where('is_administrator', 1)->first();
                    }
                    // afterCommit is needed because we are in a db transaction
                    BuildScriptExecutor::dispatch($this->model->id, $user->id)->afterCommit();
                }
                break;

            default:
                // code...
                break;
        }

        return true;
    }
}
