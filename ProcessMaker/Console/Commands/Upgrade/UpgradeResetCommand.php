<?php

namespace ProcessMaker\Console\Commands\Upgrade;

use Illuminate\Console\Command;

class UpgradeResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all upgrade migrations';

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
        $this->call('migrate:reset', [
            '--path' => 'upgrades'
        ]);
    }
}
