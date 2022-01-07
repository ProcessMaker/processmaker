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

        $this->processOrphanScheduledTask();
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
        $fileName = 'unhandled_error.txt';
        if (file_exists($fileName)) {
            $content = file_get_contents($fileName);
            $tokens = explode(',', $content);
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
        $this->info('directorio' . getcwd());
    }

    private function processOrphanScheduledTask()
    {
        $scheduledTasks = $this->getScheduledTasks();
        $toRemove = [];
        foreach ($scheduledTasks as $scheduled) {
            $config = json_decode($scheduled->configuration, true);
            //print_r($scheduled->configuration);
            $elementId = $config['element_id'];
            $this->info('config: ' . print_r($config['element_id'], true));
            $process = $scheduled->process;
            $definition = $process->getBpmnDefinition();
            $node = $this->getElementById($definition, $elementId);
            if (!$node) {
               $toRemove[]  = $node;
            }
        }
    }

    private function getTaskList()
    {
        $tasks = ProcessRequestToken::whereIn('status', array('FAILING', 'ACTIVE'))->whereIn('element_type', ['scriptTask', 'serviceTask']);
        return $tasks->get();
    }

    private function getScheduledTasks()
    {
        return $scheduled = ScheduledTask::all();
    }
    private function getElementById($definitions, $id)
    {
        $x = new DOMXPath($this->definitions);
        return $x->query("//*[@id='$id']")->item(0);
    }
}
