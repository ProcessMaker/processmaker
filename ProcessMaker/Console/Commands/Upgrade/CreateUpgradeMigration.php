<?php

namespace ProcessMaker\Console\Commands\Upgrade;

use Illuminate\Console\Command;

class CreateUpgradeMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:create {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an upgrade migration';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('make:migration', [
            'name' => $this->argument('name'),
            '--path' => 'upgrades'
        ]);
    }
}
