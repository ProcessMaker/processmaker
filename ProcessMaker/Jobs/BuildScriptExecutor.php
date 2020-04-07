<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BuildScriptExecutor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // These will be send with the payload
    protected $lang;
    protected $userId;

    // Do not retry this job if it fails
    public $tries = 1;
    
    // Building can take some time
    public $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $lang, $userId)
    {
        $this->lang = $lang;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Artisan::call('processmaker:build-script-executor ' . $this->lang . ' ' . $this->userId . ' --rebuild');
    }
}
