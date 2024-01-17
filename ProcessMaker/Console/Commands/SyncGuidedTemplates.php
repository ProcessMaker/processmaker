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
        if ($this->option('queue')) {
            $randomDelay = random_int(10, 120);
            Job::dispatch()->delay(now()->addMinutes($randomDelay));
        } else {
            Job::dispatchSync();
        }

        Job::dispatchSync();

        return 0;
    }
}
