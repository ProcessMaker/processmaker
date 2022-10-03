<?php

namespace ProcessMaker\Upgrades\Commands;

use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use ProcessMaker\Upgrades\UpgradeCreator;

class UpgradeMakeCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:upgrade 
        {name : The name of the upgrade}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new upgrade file';

    /**
     * The migration creator instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationCreator  $creator
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(UpgradeCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $this->writeMigration(
            Str::snake(trim($this->input->getArgument('name')))
        );
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     *
     * @throws \Exception
     */
    protected function writeMigration($name)
    {
        $file = $this->creator->createUpgrade(
            $name, $this->getMigrationPath()
        );

        // Once we've written the migration out, we will dump-autoload
        // for the entire framework to make sure that the migrations
        // are registered by the class loaders.
        $this->line('<info>Dumping/optimizing the composer autoload file...:</info>');
        $this->composer->dumpOptimized();

        $this->line("<info>Created Upgrade File:</info> {$file}");
    }
}
