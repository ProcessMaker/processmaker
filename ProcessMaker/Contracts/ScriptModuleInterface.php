<?php

namespace ProcessMaker\Contracts;

use ProcessMaker\ScriptRuntime\ScriptExecutionContext;

/**
 * A package-provided module exposed to allow_in_process PHP scripts.
 *
 * Scripts access the module as ${key}, e.g. $collections->all().
 */
interface ScriptModuleInterface
{
    /**
     * Unique key used as the script variable name (e.g. "collections").
     */
    public static function key(): string;

    /**
     * Human-readable label for docs / UI catalog.
     */
    public static function label(): string;

    /**
     * Method catalog for discovery (key => meta).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalog(): array;

    /**
     * Prepare the module for a single script execution.
     */
    public function boot(ScriptExecutionContext $context): void;
}
