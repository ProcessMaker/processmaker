<?php

namespace ProcessMaker\Console\Commands\Upgrade;

use RuntimeException;
use Composer\Semver\Comparator;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Migrations\Migrator;

class UpgradeCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade 
                            {--to= : The target version we\'re upgrading to}';

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
    public function __construct(Migrator $migrator)
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

        $this->migrator->setOutput($this->output);

        $this->migrator->run($this->getMigrationPaths(), [
            'to' /********/ => $this->getToVersion(),
            'current' /***/ => $this->getCurrentVersion()
        ]);
    }

    /**
     * Prepare the migration database for running.
     *
     * @return void
     */
    protected function prepareDatabase()
    {
        $this->migrator->setConnection($this->getDatabase());

        if (!$this->migrator->repositoryExists()) {
            $this->call('upgrade:install', [
                '--database' => $this->getDatabase()
            ]);
        }
    }
}
