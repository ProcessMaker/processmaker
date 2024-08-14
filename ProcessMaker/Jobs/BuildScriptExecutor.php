<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class BuildScriptExecutor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // These will be send with the payload
    protected $lang;

    protected $userId;

    // Do not retry this job if it fails
    public $tries = 10;

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

    /**
     * Prevent job overlaps
     *
     * @return WithoutOverlapping[]
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping())->releaseAfter(30)->expireAfter(630)];
    }
}
