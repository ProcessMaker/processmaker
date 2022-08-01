<?php

namespace ProcessMaker\Upgrades\Commands;

use Illuminate\Console\ConfirmableTrait;
use ProcessMaker\Upgrades\UpgradeMigrator;
use Symfony\Component\Console\Input\InputOption;

class UpgradeResetCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'upgrade:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all upgrade migrations';

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

        // First, we'll make sure that the migration table actually exists before we
        // start trying to rollback and re-run all of the migrations. If it's not
        // present we'll just bail out with an info message for the developers.
        if (!$this->migrator->repositoryExists()) {
            return $this->comment('Upgrade migration table not found.');
        }

        $this->migrator->setOutput($this->output)->reset(
            $this->getMigrationPaths(), $this->option('pretend')
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['pretend', null, InputOption::VALUE_NONE, 'Dry run the rollback of previously run upgrade migrations'],
        ];
    }
}
