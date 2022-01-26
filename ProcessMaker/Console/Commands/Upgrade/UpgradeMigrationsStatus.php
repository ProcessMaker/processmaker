<?php

namespace ProcessMaker\Console\Commands\Upgrade;

use Illuminate\Console\Command;

class UpgradeMigrationsStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each upgrade migration';

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
     * @return mixed
     */
    public function handle()
    {
        $this->call('migrate:status', [
            '--path' => 'upgrades'
        ]);
    }
}
