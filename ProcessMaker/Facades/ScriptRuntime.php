<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\ScriptRuntime\ScriptExecutionContext;
use ProcessMaker\ScriptRuntime\ScriptModuleRegistry;

/**
 * @method static void registerModule(string $moduleClass, ?string $key = null)
 * @method static ScriptModuleRegistry registry()
 * @method static array catalog()
 * @method static mixed run(string $code, ScriptExecutionContext $context)
 * @method static array normalizeOutput(mixed $output)
 *
 * @see \ProcessMaker\ScriptRuntime\ScriptRuntime
 */
class ScriptRuntime extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'script.runtime';
    }
}
