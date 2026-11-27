<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use ProcessMaker\Models\ScriptExecutor;

class ScriptExecutorResolver
{
    public function resolveForNewScript(): ?ScriptExecutor
    {
        $php = ScriptExecutor::query()
            ->where('language', 'php')
            ->orderBy('id')
            ->first();
        if ($php !== null) {
            return $php;
        }

        return ScriptExecutor::query()
            ->where('language', '!=', 'php-nayra')
            ->orderBy('id')
            ->first()
            ?? ScriptExecutor::query()->orderBy('id')->first();
    }
}
