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

    public function __construct(UpgradeMigrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return void|int
     */
    public function handle()
    {
        if (!$this->migrator->repositoryExists()) {
            $this->error('No upgrade migrations found.');

            return 1;
        }

        $ran = $this->migrator->getRepository()->getRan();

        $batches = $this->migrator->getRepository()->getMigrationBatches();

        if (count($migrations = $this->getStatusFor($ran, $batches)) > 0) {
            $this->table(['Ran?', 'Upgrade Migration'], $migrations);
        } else {
            $this->error('No upgrade migrations found');
        }
    }

    /**
     * Get the status for the given ran migrations.
     *
     * @param  array  $ran
     * @param  array  $batches
     * @return \Illuminate\Support\Collection
     */
    protected function getStatusFor(array $ran, array $batches)
    {
        return Collection::make($this->getAllMigrationFiles())
                         ->map(function ($migration) use ($ran, $batches) {
                             $migrationName = $this->migrator->getMigrationName($migration);

                             return in_array($migrationName, $ran)
                                 ? ['<info>Yes</info>', $migrationName, $batches[$migrationName]]
                                 : ['<fg=red>No</fg=red>', $migrationName];
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
