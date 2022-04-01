<?php

namespace ProcessMaker\Upgrades\Commands;

use Illuminate\Support\Collection;
use ProcessMaker\Upgrades\UpgradeMigrator;

class UpgradeStatusCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each upgrade migration';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator $migrator
     * @return \Illuminate\Database\Console\Migrations\StatusCommand
     */
    public function __construct(UpgradeMigrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->migrator->setConnection($this->getDatabase());

        if (!$this->migrator->repositoryExists()) {
            return $this->error('No upgrade migrations found.');
        }

        $ran = $this->migrator->getRepository()->getRan();

        if (count($migrations = $this->getStatusFor($ran)) > 0) {
            $this->table(['Ran?', 'Migration'], $migrations);
        } else {
            $this->error('No migrations found');
        }
    }

    /**
     * Get the status for the given ran migrations.
     *
     * @param  array  $ran
     * @return \Illuminate\Support\Collection
     */
    protected function getStatusFor(array $ran)
    {
        $this->migrator->requireFiles(
            $files = $this->getAllMigrationFiles()
        );

        $sorted_migrations = Collection::make(
            $this->migrator->sortBySemanticVersion($files)
        );

        return $sorted_migrations->map(function ($migration) use ($ran) {
            $migration_name = $this->migrator->getMigrationName($migration);

            return in_array($migration_name, $ran)
                ? ['<info>Y</info>', $migration_name]
                : ['<fg=red>N</fg=red>', $migration_name];
        });
    }

    /**
     * Get an array of all of the migration files.
     *
     * @return array
     */
    protected function getAllMigrationFiles()
    {
        return $this->migrator->getMigrationFiles($this->getMigrationPaths());
    }
}
