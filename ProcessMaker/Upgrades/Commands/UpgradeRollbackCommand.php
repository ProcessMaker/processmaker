<?php

namespace ProcessMaker\Upgrades\Commands;

use Illuminate\Console\ConfirmableTrait;
use ProcessMaker\Upgrades\UpgradeMigrator;

class UpgradeRollbackCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:rollback
                            {--pretend : Dry run the rollback of already run upgrade migrations}
                            {--step : The number of upgrade migration to be reverted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all run upgrade migrations';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @return void
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
        if (!$this->confirmToProceed()) {
            return;
        }

        $this->prepareDatabase();

        $this->migrator->rollback(
            $this->getMigrationPaths(), [
                'pretend' => $this->option('pretend'),
                'step' => (int) $this->option('step'),
            ]
        );
    }

    /**
     * Prepare the migration database for running.
     *
     * @return void
     */
    protected function prepareDatabase()
    {
        $this->migrator->setOutput($this->output);

        if (!$this->migrator->repositoryExists()) {
            $this->call('upgrade:install');
        }
    }
}
