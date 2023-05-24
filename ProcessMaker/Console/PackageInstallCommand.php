<?php

namespace ProcessMaker\Console;

use Illuminate\Console\Command;

abstract class PackageInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
        $this->addOption('preinstall', null, null, 'Run preinstall tasks')
            ->addOption('install', null, null, 'Run install tasks')
            ->addOption('postinstall', null, null, 'Run postinstall tasks');
    }

    /**
     * Preinstall Tasks. This method will be executed before install() method.
     *
     * This method must execute task that not required database connection like publish assets, copy files, etc.
     * @return void
     */
    abstract protected function preinstall();

    /**
     * Install Tasks. This method will be executed after preinstall() method and before postinstall() method.
     *
     * This method must execute task that required database connection like migrations, upgrade tasks etc.
     * Avoid to include tasks that could require external services like API calls, or trigger events and jobs
     */
    abstract protected function install();

    /**
     * Postinstall Tasks. This method will be executed after install() method.
     *
     * This method must execute tasks that required database connections, API calls or trigger events and jobs.
     */
    abstract protected function postinstall();

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->isFullInstall()) {
            $this->preinstall();
            $this->install();
            $this->postinstall();

            return;
        }
        if ($this->option('preinstall')) {
            $this->preinstall();
        }
        if ($this->option('install')) {
            $this->install();
        }
        if ($this->option('postinstall')) {
            $this->postinstall();
        }
    }

    /**
     * Determine if the command is a full install.
     * @return bool
     */
    private function isFullInstall()
    {
        $options = ['preinstall', 'install', 'postinstall'];

        return !array_sum(array_map(function ($option) {
            return $this->option($option) ? 1 : 0;
        }, $options));
    }
}
