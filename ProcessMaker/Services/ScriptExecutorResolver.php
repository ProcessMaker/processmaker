<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use ProcessMaker\Models\ScriptExecutor;

class ScriptExecutorResolver
{
    public const DEPLOY_LANGUAGE = 'php';

    /**
     * Find the executor a deploy request asked for, by id or by exact title.
     * The id wins when both are sent. Returns null when none was requested
     * or when it does not exist.
     */
    public function findRequested(?int $id, ?string $title): ?ScriptExecutor
    {
        if ($id !== null) {
            return ScriptExecutor::query()->find($id);
        }
        if ($title !== null && $title !== '') {
            return ScriptExecutor::query()->where('title', $title)->first();
        }

        return null;
    }

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
