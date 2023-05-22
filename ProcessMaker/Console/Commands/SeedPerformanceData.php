<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Tests\Performance\RequestListingPerformanceData;

class SeedPerformanceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:seed-performance-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return int
     */
    public function handle()
    {
        $seeder = new RequestListingPerformanceData();
        $seeder->requestCount = 100_000;
        $seeder->processCount = 10;
        $seeder->userCount = 2_000;
        $seeder->run();
        $seeder->associateWithUser(50, 50, 50);
    }
}
