<?php

namespace ProcessMaker\ScriptRuntime;

use InvalidArgumentException;
use ProcessMaker\Contracts\ScriptModuleInterface;

class ScriptModuleRegistry
{
    public const RESERVED_KEYS = ['data', 'config', 'modules'];

    /**
     * @var array<string, class-string<ScriptModuleInterface>>
     */
    private array $modules = [];

    /**
     * Register a module class. Key is taken from Module::key() unless overridden.
     *
     * @param  class-string<ScriptModuleInterface>  $moduleClass
     * @param  string|null  $key  Optional override
     */
    public function register(string $moduleClass, ?string $key = null): void
    {
        if (!is_subclass_of($moduleClass, ScriptModuleInterface::class)) {
            throw new InvalidArgumentException(
                $moduleClass . ' must implement ' . ScriptModuleInterface::class
            );
        }

        $resolvedKey = $key ?: $moduleClass::key();
        $resolvedKey = strtolower(trim($resolvedKey));

        if ($resolvedKey === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $resolvedKey)) {
            throw new InvalidArgumentException('Invalid script module key: ' . $resolvedKey);
        }

        if (in_array($resolvedKey, self::RESERVED_KEYS, true)) {
            throw new InvalidArgumentException(
                'Script module key is reserved: ' . $resolvedKey
            );
        }

        $this->modules[$resolvedKey] = $moduleClass;
    }

    public function has(string $key): bool
    {
        return isset($this->modules[strtolower($key)]);
    }

    /**
     * @return class-string<ScriptModuleInterface>|null
     */
    public function get(string $key): ?string
    {
        return $this->modules[strtolower($key)] ?? null;
    }

    /**
     * @return array<string, class-string<ScriptModuleInterface>>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->modules);
    }

    /**
     * Aggregated catalog for discovery APIs / docs.
     *
     * @return list<array<string, mixed>>
     */
    public function catalog(): array
    {
        $items = [];
        foreach ($this->modules as $key => $class) {
            $items[] = [
                'key' => $key,
                'label' => $class::label(),
                'class' => $class,
                'methods' => $class::catalog(),
            ];
        }

        return $items;
    }
}
