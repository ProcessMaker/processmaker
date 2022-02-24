<?php

namespace ProcessMaker\Console\Commands\Upgrade;

use ProcessMaker\Managers\Upgrade\UpgradeCreator;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;

class UpgradeMakeCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:upgrade {name : The name of the upgrade}
        {--path= : The location where the upgrade file should be created}';

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

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigration($name);

        $this->composer->dumpAutoloads();
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
        $file = $this->creator->create(
            $name, $this->getMigrationPath()
        );

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

        return base_path('upgrades');
    }
}
