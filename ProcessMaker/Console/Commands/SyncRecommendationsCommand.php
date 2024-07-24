<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use ProcessMaker\Jobs\SyncRecommendations as SyncRecommendationsJob;
use ProcessMaker\SyncRecommendations;

class SyncRecommendationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:sync-recommendations
                            {--queue : Queue this command to run asynchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs recommendations from GitHub';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($this->option('queue')) {
            $randomDelay = random_int(10, 120);
            SyncRecommendationsJob::dispatch()->delay(now()->addMinutes($randomDelay));

            return;
        }

        $this->line('Syncing recommendations from GitHub...');

        try {
            app(SyncRecommendations::class)->sync();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        } finally {
            $this->line('Sync complete');
        }
    }
}
