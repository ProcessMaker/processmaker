<?php

namespace ProcessMaker\Upgrades\Commands;

use Illuminate\Support\Str;
use Illuminate\Support\Composer;
use ProcessMaker\Upgrades\UpgradeCreator;

class UpgradeMakeCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:upgrade {name : The name of the upgrade}
        {--optional : Designates this upgrade as optional/can be skipped when running as a set. Defaults to required}
        {--path= : The location where the upgrade file should be created. Defaults to "upgrades"}
        {--from= : The ProcessMaker version being upgraded from. Example: "4.1.23"}
        {--to= : The ProcessMaker version being upgraded to. Example: "4.2.28"}';

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
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $name = Str::snake(trim($this->input->getArgument('name')));

        // The version we're upgrading *from* if provided
        $from = $this->input->hasOption('from')
            ? $this->input->getOption('from')
            : null;

        // The version we're upgrading *to* if provided
        $to = $this->input->hasOption('to')
            ? $this->input->getOption('to')
            : null;

        // Created upgrade migration is required when run as a set
        $optional = $this->input->hasOption('optional');

        // Now we are ready to write the migration out to disk.
        $this->writeMigration($name, $from, $to, $optional);
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     *
     * @throws \Exception
     */
    protected function writeMigration($name, $from, $to, $optional)
    {
        $file = $this->creator->createUpgrade(
            $name, $this->getMigrationPath(), $from, $to, $optional
        );

        // Once we've written the migration out, we will dump-autoload
        // for the entire framework to make sure that the migrations
        // are registered by the class loaders.
        $this->composer->dumpAutoloads();

        $this->line("<info>Created Upgrade File:</info> {$file}");
    }

    /**
     * Get migration path (either specified by '--path' option or default location).
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        if (! is_null($targetPath = $this->input->getOption('path'))) {
            return $this->laravel->basePath().'/'.$targetPath;
        }

        return $this->getMigrationPath();
    }
}
