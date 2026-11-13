<?php

namespace ProcessMaker\Services;

use ProcessMaker\Models\Script;

class ScriptExecutionModeResolver
{
    public function resolve(Script $script): string
    {
        if (!$script->allow_in_process) {
            return Script::EXECUTION_MODE_BARE;
        }

        $mode = $script->execution_mode ?: config('core-service-task.execution', Script::EXECUTION_MODE_BARE);

        if (
            $mode !== Script::EXECUTION_MODE_MODULES
            || !config('core-service-task.modules_enabled', false)
        ) {
            return Script::EXECUTION_MODE_BARE;
        }

        return Script::EXECUTION_MODE_MODULES;
    }
}
