<?php

namespace ProcessMaker;

use DB;
use Illuminate\Support\Facades\Log;

class JsonColumnIndex
{
    public function add(string $table, string $column, string $path)
    {
        $indexName = $column . '_' . $path;

        if ($this->indexExists($table, $indexName)) {
            return false;
        }

        $sql = <<<SQL
            ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ((
                LEFT({$column}->>"{$path}", 255) COLLATE utf8mb4_bin
            )) USING BTREE;
        SQL;

        if (!config('database.enable_index_json_columns')) {
            Log::warning('Indexing JSON columns is disabled. The following index was not created: ' . $sql);
            return false;
        }

        return DB::statement($sql);
    }

    public function indexExists(string $table, string $name)
    {
        return $this->listIndexes($table)->contains(function ($index) use ($name) {
            return $index->Key_name === $name;
        });
    }

    public function listIndexes(string $table)
    {
        return collect(DB::select("SHOW INDEXES FROM {$table}"));
    }

    public function remove(string $table, string $column, string $path)
    {
        $indexName = $column . '_' . $path;

        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        return DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
    }
}
