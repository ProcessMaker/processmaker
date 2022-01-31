<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ScheduledTask;

class GarbageCollector extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "
        processmaker:garbage-collect
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve issues with unhandled errors when executing requests';

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
        //TODO log to main logger too

        $this->processHaltedScripts();

        $this->processUnhandledErrors();
    }

    private function processHaltedScripts()
    {
        $this->info('Processing halted service/script tasks...');
        $tasks = $this->getTaskList();

        if (!$tasks->count()) {
            $this->info('No failing script or service tasks found.');
        }

        $bar = $this->output->createProgressBar($tasks->count());
        $bar->start();


        $requestIds = $tasks->pluck('process_request_id');

        foreach ($tasks as $token) {
            $bar->advance();
            $instance = $token->processRequest;
            $process = $instance->process;

            if ($token->element_type === 'scriptTask') {
                RunScriptTask::dispatch($process, $instance, $token, []);
            }
            if ($token->element_type === 'serviceTask') {
                RunServiceTask::dispatch($process, $instance, $token, []);
            }
        }
        $bar->finish();
    }

    private function processUnhandledErrors()
    {
        $fileName = storage_path('app/private') . '/unhandled_error.txt';
        if (file_exists($fileName)) {
            $tokens = [];
            if ($file = fopen($fileName, "r")) {
                while(!feof($file)) {
                    $token = fgets($file);
                    $tokens[] = trim($token);
                }
                fclose($file);
            }

            foreach ($tokens as $tokenId) {
                $token = ProcessRequestToken::find($tokenId);
                if ($token) {
                    $instance = $token->processRequest;
                    $process = $instance->process;
                    if ($token->element_type === 'scriptTask') {
                        RunScriptTask::dispatch($process, $instance, $token, []);
                    }
                    if ($token->element_type === 'serviceTask') {
                        RunServiceTask::dispatch($process, $instance, $token, []);
                    }
                }
            }
            unlink($fileName);
        }
    }

    private function getTaskList()
    {
        $tasks = ProcessRequestToken::whereIn('status', array('FAILING', 'ACTIVE'))->whereIn('element_type', ['scriptTask', 'serviceTask']);
        return $tasks->get();
    }
}
