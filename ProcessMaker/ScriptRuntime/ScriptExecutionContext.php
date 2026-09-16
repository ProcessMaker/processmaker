<?php

namespace ProcessMaker\ScriptRuntime;

use ProcessMaker\Models\User;

/**
 * Per-run context passed into script modules.
 */
class ScriptExecutionContext
{
    public function __construct(
        public readonly array $data,
        public readonly array $config,
        public readonly ?User $user,
        public readonly string $tokenId = '',
        public readonly int $timeout = 0,
        public readonly ?int $scriptId = null,
        public readonly string $source = 'service-task',
    ) {
    }
}
