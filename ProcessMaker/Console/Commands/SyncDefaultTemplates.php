<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Jobs\SyncDefaultTemplates as Job;

class SyncDefaultTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:sync-default-templates
                            {--queue : Queue this command to run asynchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize default templates from a central repository';

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
            $randomDelay = rand(10, 120);
            Job::dispatch()->delay(now()->addMinutes($randomDelay));
        } else {
            Job::dispatchNow();
        }

        return 0;
    }
}
