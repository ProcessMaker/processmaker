<?php

namespace ProcessMaker\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CachedSchema
{
    public function hasTable(string $table) : bool
    {
        $key = 'hasTable_' . $table;

        return Cache::remember($key, 86400, function () use ($table) {
            return Schema::hasTable($table);
        });
    }

    public function hasColumn(string $table, string $column) : bool
    {
        $key = 'hasColumn_' . $table . '_' . $column;

        return Cache::remember($key, 86400, function () use ($table, $column) {
            return Schema::hasColumn($table, $column);
        });
    }
}
