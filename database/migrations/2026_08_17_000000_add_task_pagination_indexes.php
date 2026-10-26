<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLE = 'process_request_tokens';

    private const COUNT_INDEX = 'process_request_tokens_prt_completed_task_count';

    private const PAGE_INDEX = 'process_request_tokens_prt_task_created';

    /**
     * Add indexes used by completed-task pagination.
     *
     * Run this migration during a low-traffic window. Although MySQL builds the
     * indexes online, it can briefly wait for metadata locks on a large table.
     */
    public function up(): void
    {
        $clauses = [];

        if (!Schema::hasIndex(self::TABLE, self::COUNT_INDEX)) {
            $clauses[] = sprintf(
                'ADD INDEX `%s` (`status`, `is_self_service`, `element_type`, `element_name`)',
                self::COUNT_INDEX
            );
        }

        if (!Schema::hasIndex(self::TABLE, self::PAGE_INDEX)) {
            $clauses[] = sprintf(
                'ADD INDEX `%s` (`is_self_service`, `created_at` DESC, `status`, `element_type`, `element_name`)',
                self::PAGE_INDEX
            );
        }

        $this->alterTable($clauses);
        $this->refreshStatistics();
    }

    /**
     * Remove completed-task pagination indexes.
     */
    public function down(): void
    {
        $clauses = [];

        if (Schema::hasIndex(self::TABLE, self::COUNT_INDEX)) {
            $clauses[] = sprintf('DROP INDEX `%s`', self::COUNT_INDEX);
        }

        if (Schema::hasIndex(self::TABLE, self::PAGE_INDEX)) {
            $clauses[] = sprintf('DROP INDEX `%s`', self::PAGE_INDEX);
        }

        $this->alterTable($clauses);
        $this->refreshStatistics();
    }

    private function alterTable(array $clauses): void
    {
        if ($clauses === []) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` %s, ALGORITHM=INPLACE, LOCK=NONE',
            self::TABLE,
            implode(', ', $clauses)
        ));
    }

    private function refreshStatistics(): void
    {
        DB::statement(sprintf('ANALYZE TABLE `%s`', self::TABLE));
    }
};
