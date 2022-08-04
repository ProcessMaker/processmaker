<?php

namespace ProcessMaker\Upgrades\Commands;

use Illuminate\Database\Migrations\MigrationRepositoryInterface;

class UpgradeInstallCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'upgrade:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the upgrades migration repository';

    /**
     * The repository instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationRepositoryInterface  $repository
     * @return void
     */
    public function __construct(MigrationRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->repository->repositoryExists()) {
            return $this->warn('Upgrade migration table already exists.');
        }

        $this->repository->createRepository();

        $this->info('Upgrade migration table created successfully.');
    }
}
