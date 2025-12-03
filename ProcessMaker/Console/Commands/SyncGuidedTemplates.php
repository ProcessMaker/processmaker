<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Jobs\SyncGuidedTemplates as Job;

class SyncGuidedTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:sync-guided-templates
                            {--queue : Queue this command to run asynchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize guided templates from a central repository';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('queue')) {
            $randomDelay = random_int(10, 120);
            Job::dispatch()->delay(now()->addMinutes($randomDelay));

            return 0;
        }

        Job::dispatchSync();

        return 0;
    }
}
