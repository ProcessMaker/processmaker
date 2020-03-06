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

    protected $lang;
    protected $userId;

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
        \Log::info("Started artisan command to build script executor for language: " . $this->lang . " for user: " . $this->userId);
        \Artisan::call('processmaker:build-script-executor ' . $this->lang . ' ' . $this->userId);
        \Log::info("Finished running build script executor");
    }
}
