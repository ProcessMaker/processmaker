<?php

namespace ProcessMaker\Console\Commands\Upgrade;

use Illuminate\Console\Command;

class RunUpgradesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the upgrade migrations';

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
        $this->call('migrate', [
            '--path' => 'upgrades'
        ]);
    }
}
