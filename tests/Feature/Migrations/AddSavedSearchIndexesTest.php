<?php

namespace Tests\Feature\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddSavedSearchIndexesTest extends TestCase
{
    private const MIGRATION_PATH = 'database/migrations/2026_07_27_000000_add_saved_search_indexes.php';

    private const TARGET_INDEXES = [
        [
            'table' => 'category_assignments',
            'name' => 'category_assignments_assignabletyid_categorytyid',
            'columns' => ['assignable_type', 'assignable_id', 'category_type', 'category_id'],
        ],
        [
            'table' => 'process_categories',
            'name' => 'process_categories_issystem_id',
            'columns' => ['is_system'],
        ],
        [
            'table' => 'process_versions',
            'name' => 'process_versions_processid_draft',
            'columns' => ['process_id', 'draft'],
        ],
    ];

    private const MANUAL_INDEXES = [
        [
            'table' => 'category_assignments',
            'name' => 'ca_assignable_type_id_cat_type_id_idx',
            'columns' => ['assignable_type', 'assignable_id', 'category_type', 'category_id'],
        ],
        [
            'table' => 'process_categories',
            'name' => 'pc_is_system_id_idx',
            'columns' => ['is_system', 'id'],
        ],
        [
            'table' => 'process_versions',
            'name' => 'pv_process_id_draft_idx',
            'columns' => ['process_id', 'draft'],
        ],
    ];

    protected $connectionsToTransact = [];

    public function testUpCreatesTheSavedSearchIndexes(): void
    {
        $migration = $this->migration();
        $migration->down();

        try {
            $migration->up();

            foreach (self::TARGET_INDEXES as $index) {
                $this->assertIndexColumns($index['table'], $index['name'], $index['columns']);
            }
        } finally {
            $migration->up();
        }
    }

    public function testUpIsIdempotent(): void
    {
        $migration = $this->migration();
        $migration->up();
        $migration->up();

        foreach (self::TARGET_INDEXES as $index) {
            $matches = collect(Schema::getIndexes($index['table']))
                ->where('name', $index['name']);

            $this->assertCount(1, $matches);
            $this->assertSame($index['columns'], $matches->first()['columns']);
        }
    }

    public function testUpUsesEquivalentManualIndexesAndDownPreservesThem(): void
    {
        $migration = $this->migration();
        $migration->down();
        $this->dropManualIndexes();
        $this->addManualIndexes();
        $rollbackIndex = $this->addProcessVersionsRollbackIndex();

        try {
            $migration->up();

            foreach (self::TARGET_INDEXES as $index) {
                $this->assertFalse(
                    Schema::hasIndex($index['table'], $index['name']),
                    "Index {$index['name']} still exists on {$index['table']}."
                );
            }

            $this->assertFalse(
                Schema::hasIndex('process_versions', $rollbackIndex),
                "Rollback index {$rollbackIndex} still exists on process_versions."
            );

            $migration->down();

            foreach (self::MANUAL_INDEXES as $index) {
                $this->assertTrue(Schema::hasIndex($index['table'], $index['name']));
            }
        } finally {
            $this->addProcessVersionsRollbackIndex();
            $this->dropManualIndexes();
            $migration->up();
        }
    }

    public function testDownRemovesTheCanonicalIndexes(): void
    {
        $migration = $this->migration();
        $migration->up();

        try {
            $migration->down();

            foreach (self::TARGET_INDEXES as $index) {
                $this->assertFalse(
                    Schema::hasIndex($index['table'], $index['name']),
                    "Index {$index['name']} still exists on {$index['table']}."
                );
            }
        } finally {
            $migration->up();
        }
    }

    private function migration()
    {
        return include base_path(self::MIGRATION_PATH);
    }

    private function assertIndexColumns(string $table, string $name, array $columns): void
    {
        $index = collect(Schema::getIndexes($table))->firstWhere('name', $name);

        $this->assertNotNull($index);
        $this->assertSame($columns, $index['columns']);
    }

    private function addManualIndexes(): void
    {
        foreach (self::MANUAL_INDEXES as $index) {
            Schema::table($index['table'], function (Blueprint $table) use ($index) {
                $table->index($index['columns'], $index['name']);
            });
        }
    }

    private function dropManualIndexes(): void
    {
        foreach (self::MANUAL_INDEXES as $index) {
            if (!Schema::hasIndex($index['table'], $index['name'])) {
                continue;
            }

            Schema::table($index['table'], function (Blueprint $table) use ($index) {
                $table->dropIndex($index['name']);
            });
        }
    }

    private function addProcessVersionsRollbackIndex(): string
    {
        $table = 'process_versions';
        $columns = ['process_id'];
        $name = $this->rollbackIndexName($table, $columns);

        if (!Schema::hasIndex($table, $name)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                $table->index($columns, $name);
            });
        }

        return $name;
    }

    private function rollbackIndexName(string $table, array $columns): string
    {
        $suffix = '_four27824_rb_' . substr(sha1(implode(',', $columns)), 0, 8);

        return substr($table, 0, 64 - strlen($suffix)) . $suffix;
    }
}
