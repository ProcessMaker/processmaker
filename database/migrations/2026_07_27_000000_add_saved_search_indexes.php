<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add performance indexes for Saved Search queries.
     */
    public function up(): void
    {
        $this->addIndex(
            'process_request_tokens',
            'process_request_tokens_prt_task_name_id',
            ['element_type', 'element_name', 'process_id', 'user_id', 'process_request_id']
        );
        $this->addIndex(
            'process_request_tokens',
            'process_request_tokens_prt_type_id_proc',
            ['element_type', 'process_id', 'user_id', 'process_request_id']
        );
        $this->addIndex(
            'process_request_tokens',
            'process_request_tokens_prt_self_service_status_user',
            ['is_self_service', 'status', 'user_id', 'id']
        );
        $this->addIndex(
            'process_request_tokens',
            'process_request_tokens_processid_elem_stat_self_user_elname',
            ['process_id', 'element_type', 'status', 'is_self_service', 'user_id', 'element_name']
        );

        $this->addIndex(
            'category_assignments',
            'category_assignments_assignabletyid_categorytyid',
            ['assignable_type', 'assignable_id', 'category_type', 'category_id']
        );

        $this->addIndex(
            'process_versions',
            'process_versions_processid_draft',
            ['process_id', 'draft']
        );

        // Leading `id` removed from the original proposal because the primary key
        // already covers it and InnoDB secondary indexes include it automatically.
        $this->addIndex(
            'processes',
            'process_idx_proc_deletedat_categoryid',
            ['deleted_at', 'process_category_id']
        );

        // Tail `id` removed for the same reason.
        $this->addIndex(
            'process_categories',
            'process_categories_issystem_id',
            ['is_system']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndex('process_request_tokens', 'process_request_tokens_prt_task_name_id');
        $this->dropIndex('process_request_tokens', 'process_request_tokens_prt_type_id_proc');
        $this->dropIndex('process_request_tokens', 'process_request_tokens_prt_self_service_status_user');
        $this->dropIndex('process_request_tokens', 'process_request_tokens_processid_elem_stat_self_user_elname');
        $this->dropIndex('category_assignments', 'category_assignments_assignabletyid_categorytyid');
        $this->dropIndex('process_versions', 'process_versions_processid_draft');
        $this->dropIndex('processes', 'process_idx_proc_deletedat_categoryid');
        $this->dropIndex('process_categories', 'process_categories_issystem_id');
    }

    private function addIndex(string $table, string $name, array $columns): void
    {
        if (Schema::hasIndex($table, $name)) {
            $this->dropRollbackIndexes($table, $columns);
            return;
        }

        if ($this->hasCoveringIndex($table, $columns)) {
            $this->dropRollbackIndexes($table, $columns);
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($name, $columns) {
            $table->index($columns, $name);
        });

        $this->dropRollbackIndexes($table, $columns);
    }

    private function hasCoveringIndex(string $table, array $columns): bool
    {
        return collect(Schema::getIndexes($table))->contains(function (array $index) use ($columns) {
            return $this->columnsStartWith($index['columns'], $columns);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (!Schema::hasIndex($table, $name)) {
            return;
        }

        $this->ensureForeignKeysRemainIndexed($table, $name);

        DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
    }

    private function ensureForeignKeysRemainIndexed(string $table, string $name): void
    {
        $indexes = collect(Schema::getIndexes($table));
        $index = $indexes->firstWhere('name', strtolower($name));

        if (!$index) {
            return;
        }

        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (!$this->columnsStartWith($index['columns'], $foreignKey['columns'])) {
                continue;
            }

            $hasReplacement = $indexes->contains(function (array $candidate) use ($index, $foreignKey) {
                return $candidate['name'] !== $index['name']
                    && $this->columnsStartWith($candidate['columns'], $foreignKey['columns']);
            });

            if ($hasReplacement) {
                continue;
            }

            $replacementName = $this->rollbackIndexName($table, $foreignKey['columns']);

            Schema::table($table, function (Blueprint $table) use ($foreignKey, $replacementName) {
                $table->index($foreignKey['columns'], $replacementName);
            });

            $indexes = collect(Schema::getIndexes($table));
        }
    }

    private function dropRollbackIndexes(string $table, array $columns): void
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (!$this->columnsStartWith($columns, $foreignKey['columns'])) {
                continue;
            }

            $name = $this->rollbackIndexName($table, $foreignKey['columns']);

            if (Schema::hasIndex($table, $name)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
            }
        }
    }

    private function rollbackIndexName(string $table, array $columns): string
    {
        $suffix = '_four27824_rb_' . substr(sha1(implode(',', $columns)), 0, 8);

        return substr($table, 0, 64 - strlen($suffix)) . $suffix;
    }

    private function columnsStartWith(array $indexColumns, array $columns): bool
    {
        return array_slice($indexColumns, 0, count($columns)) === $columns;
    }
};
