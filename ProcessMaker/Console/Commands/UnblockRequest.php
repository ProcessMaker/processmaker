<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Bpmn\Models\ScriptTask;
use ProcessMaker\Nayra\Bpmn\Models\ServiceTask;

class UnblockRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        processmaker:unblock-request
            {--request= : The ID # of the Request to retry}
    ';

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
     * @return mixed
     */
    public function handle()
    {
        $requestId = $this->option('request');
        $this->info("Processing request $requestId...");

        $request = ProcessRequest::find($requestId);
        if (empty($request)) {
            $this->error("Request $requestId  does not exist.");

            return;
        }
        $definitions = $request->process->getDefinitions();

        // unlock the request
        ProcessRequestLock::where('process_request_id', $requestId)->delete();
        $processed = false;
        foreach ($this->getOpenTasks($requestId) as $token) {
            $task = $definitions->getEvent($token->element_id);
            $instance = $token->processRequest;
            $process = $instance->process;

            switch (get_class($task)) {
                case ScriptTask::class:
                    $processed = true;
                    $this->info("Request $requestId: Running Script Task '$token->element_id' ($token->element_name) again...");
                    RunScriptTask::dispatch($process, $instance, $token, []);
                    break;
                case ServiceTask::class:
                    $processed = true;
                    $this->info("Request $requestId: Running Service Task '$token->element_id' ($token->element_name) again...");
                    RunServiceTask::dispatch($process, $instance, $token, []);
                    break;
            }
        }
        if (!$processed) {
            $this->info("Request $requestId: no pending scripts found.");
        }
    }

    /**
     * Find all script and service tasks of a request that are halted for some reason
     *
     * @param $requestId
     * @return mixed
     */
    private function getOpenTasks($requestId)
    {
        return ProcessRequestToken::whereIn('status', ['FAILING', 'ACTIVE', 'ERROR'])
            ->whereIn('element_type', ['scriptTask', 'serviceTask', 'task'])
            ->where('process_request_id', $requestId)
            ->get()
            ->all();
    }
}
