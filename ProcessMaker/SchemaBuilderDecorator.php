<?php

namespace ProcessMaker;

class SchemaBuilderDecorator
{
    public function __construct(private $original)
    {
    }

    public function hasTable(string $table, bool $verified = false): bool
    {
        if (!app()->runningInConsole() && !$verified) {
            \Log::info('Attempted to call Schema::hasTable() in non-console mode');
            throw new \Exception('Not allowed to call Schema::hasTable() in non-console mode');
        }

        return $this->original->hasTable($table);
    }

    public function __call($method, $parameters)
    {
        return $this->original->{$method}(...$parameters);
    }
}
