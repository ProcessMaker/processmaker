<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use Illuminate\Support\Str;
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

    public SyncRecommendations $syncRecommendations;

    public function __construct(SyncRecommendations $syncRecommendations)
    {
        parent::__construct();

        $this->syncRecommendations = $syncRecommendations;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->line('Syncing recommendations from GitHub...');

        try {
            $this->syncRecommendations->sync();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        } finally {
            $this->line('Sync complete');
        }
    }
}
