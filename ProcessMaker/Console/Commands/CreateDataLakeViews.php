<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateDataLakeViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:create-data-lake-views {--drop} {--preview}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create/replace and delete data lake views';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $drop = $this->option('drop');
        $preview = !!$this->option('preview');
        if ($drop) {
            $this->info('Dropping views...' . PHP_EOL);
            $this->down($preview);
        } else {
            $this->info('Creating or replacing views...' . PHP_EOL);
            $this->up($preview);
        }
        $this->info('Done.');
        return 0;
    }

    /**
     * @param bool $preview
     * @return void
     */
    public function up(bool $preview): void
    {
        $tables = $this->getTables();
        $views = $this->getViews();
        foreach ($tables as $tableName) {
            $columns = $this->getTableColumns($tableName);
            if (!$this->shouldCreate($views, $tableName, $columns)) {
                continue;
            }
            $aliases = [];
            foreach ($columns as $column) {
                $aliases[] = sprintf('`%s` AS `%s`', $column, $this->parseColumnName($column));
            }
            $sql = sprintf('CREATE OR REPLACE VIEW %s AS SELECT %s FROM `%s`;',
                $this->getViewName($tableName),
                implode(', ', $aliases),
                $tableName
            );
            if ($preview) {
                $this->comment($sql . PHP_EOL);
            } else {
                DB::statement($sql);
            }
        }
    }

    /**
     * @param bool $preview
     * @return void
     */
    public function down(bool $preview): void
    {
        foreach ($this->getTables() as $tableName) {
            $viewName = $this->getViewName($tableName);
            $sql = sprintf('DROP VIEW IF EXISTS `%s`;', $viewName);
            if ($preview) {
                $this->comment($sql . PHP_EOL);
            } else {
                DB::statement($sql);
            }
        }
    }

    /**
     * @param array $views
     * @param string $tableName
     * @param array $columns
     * @return bool
     */
    protected function shouldCreate(array $views, string $tableName, array $columns): bool
    {
        $viewName = $this->getViewName($tableName);
        if (!isset($views[$viewName])) {
            return true;
        }
        $sql = $views[$viewName]->getSql();
        foreach ($columns as $column) {
            if (stripos($sql, sprintf('`%s`', $column)) === false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function getViewName(string $tableName): string
    {
        return 'dlv_' . $tableName;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function parseColumnName(string $name): string
    {
        return strtolower($name) . '_';
    }

    /**
     * @param string $tableName
     * @return string[]
     */
    protected function getTableColumns(string $tableName): array
    {
        return Schema::getColumnListing($tableName);
    }

    /**
     * @return string[]
     */
    protected function getTables(): array
    {
        return DB::connection()->getDoctrineSchemaManager()->listTableNames();
    }

    /**
     * @return string[]
     */
    protected function getViews(): array
    {
        return DB::connection()->getDoctrineSchemaManager()->listViews();
    }
}