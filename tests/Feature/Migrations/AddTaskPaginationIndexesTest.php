<?php

namespace Tests\Feature\Migrations;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddTaskPaginationIndexesTest extends TestCase
{
    private const MIGRATION_PATH = 'database/migrations/2026_08_17_000000_add_task_pagination_indexes.php';

    private const COUNT_INDEX = 'idx_prt_completed_task_count';

    private const PAGE_INDEX = 'idx_prt_task_created';

    protected $connectionsToTransact = [];

    public function testUpCreatesBothIndexesAndRefreshesStatistics(): void
    {
        $migration = $this->migration();
        $migration->down();

        try {
            $queries = $this->captureQueries(fn () => $migration->up());

            $this->assertTrue(Schema::hasIndex('process_request_tokens', self::COUNT_INDEX));
            $this->assertTrue(Schema::hasIndex('process_request_tokens', self::PAGE_INDEX));
            $this->assertStatisticsWereRefreshed($queries);
        } finally {
            $migration->up();
        }
    }

    public function testUpIsIdempotentAndStillRefreshesStatistics(): void
    {
        $migration = $this->migration();
        $migration->up();

        $queries = $this->captureQueries(fn () => $migration->up());

        $this->assertTrue(Schema::hasIndex('process_request_tokens', self::COUNT_INDEX));
        $this->assertTrue(Schema::hasIndex('process_request_tokens', self::PAGE_INDEX));
        $this->assertStatisticsWereRefreshed($queries);
        $this->assertFalse(collect($queries)->contains(
            fn (string $query) => str_starts_with($query, 'ALTER TABLE `process_request_tokens`')
        ));
    }

    public function testDownRemovesBothIndexesAndRefreshesStatistics(): void
    {
        $migration = $this->migration();
        $migration->up();

        try {
            $queries = $this->captureQueries(fn () => $migration->down());

            $this->assertFalse(Schema::hasIndex('process_request_tokens', self::COUNT_INDEX));
            $this->assertFalse(Schema::hasIndex('process_request_tokens', self::PAGE_INDEX));
            $this->assertStatisticsWereRefreshed($queries);
        } finally {
            $migration->up();
        }
    }

    private function migration()
    {
        return include base_path(self::MIGRATION_PATH);
    }

    private function captureQueries(callable $callback): array
    {
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $callback();

        return $queries;
    }

    private function assertStatisticsWereRefreshed(array $queries): void
    {
        $this->assertContains('ANALYZE TABLE `process_request_tokens`', $queries);
    }
}
