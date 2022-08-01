<?php

namespace ProcessMaker\Upgrades\Commands;

use Illuminate\Console\ConfirmableTrait;
use ProcessMaker\Upgrades\UpgradeMigrator;

class UpgradeCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade
                            {--pretend : Dry run the pending upgrade migrations}
                            {--force : Force the upgrades to run even in production}
                            {--step : Force the upgrade migrations to be run so they can be rolled back individually}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the upgrade migrations';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration command instance.
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
     *
     * @throws \ProcessMaker\Exception\InvalidSemanticVersion
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->prepareDatabase();

        $this->migrator->run($this->getMigrationPaths(), [
            'force' => $this->option('force'),
            'pretend' => $this->option('pretend'),
            'step' => $this->option('step'),
        ]);
    }

    /**
     * Prepare the migration database for running.
     *
     * @return void
     */
    protected function prepareDatabase()
    {
        $this->migrator->setOutput($this->output);

        if (! $this->migrator->repositoryExists()) {
            $this->call('upgrade:install');
        }
    }
}
