<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\SyncRecommendations;

class SyncRecommendationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:sync-recommendations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs recommendations from GitHub';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncRecommendations::sync();
    }
}
