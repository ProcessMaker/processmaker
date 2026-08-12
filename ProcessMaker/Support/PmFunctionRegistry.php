<?php

namespace ProcessMaker\Support;

/**
 * Bounded registry for PM functions.
 *
 * Stores callables by name and supports resetting to boot-time state.
 * Designed as a standalone utility to avoid static state in FormalExpression.
 */
class PmFunctionRegistry
{
    private static array $functions = [];

    private static array $bootFunctions = [];

    private static bool $bootTracking = true;

    public static function register(string $name, callable $callable): void
    {
        self::$functions[$name] = $callable;

        if (self::$bootTracking && !isset(self::$bootFunctions[$name])) {
            self::$bootFunctions[$name] = $callable;
        }
    }

    public static function all(): array
    {
        return self::$functions;
    }

    public static function reset(): void
    {
        self::$bootTracking = false;
        self::$functions = self::$bootFunctions;
    }

    /**
     * Clear all registered functions.
     *
     * Intended for test isolation or complete teardown.
     */
    public static function clear(): void
    {
        self::$functions = [];
        self::$bootFunctions = [];
        self::$bootTracking = true;
    }
}
