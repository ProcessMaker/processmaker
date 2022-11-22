<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Bpmn\Models\ScriptTask;
use ProcessMaker\Nayra\Bpmn\Models\ServiceTask;
use ProcessMaker\RetryProcessRequest;

class UnblockRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:unblock-request {--request= : The ID # of the request to retry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to unblock all halted script and service tasks of a request';

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
    public function handle(): int
    {
        $requestId = $this->option('request');

        if (blank($request = ProcessRequest::find($requestId))) {
            $this->error(__("ProcessRequest::{$requestId} was not found."));

            return 1;
        }

        $retryRequest = RetryProcessRequest::for($request);

        if (!$retryRequest->hasRetriableTasks()) {
            $this->warn("ProcessRequest::{$requestId}: No retriable tasks found.");

            return 1;
        }

        $retryRequest->retry();

        foreach ($retryRequest::$output as $line) {
            $this->info($line);
        }

        return 0;
    }
}
