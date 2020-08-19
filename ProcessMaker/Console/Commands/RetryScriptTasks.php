<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessRequestLock;

class RetryScriptTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "
        processmaker:retry-script-tasks
            {--process= : The ID # of the Process to retry}
            {--request= : The ID # of the Request to retry}
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed script tasks for a specific request or process';

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
        $tasks = $this->retrieveTaskList();

        if (!$tasks->count()) {
            exit($this->error('No failing script tasks found.'));
        }

        $requestIds = $tasks->pluck('process_request_id');
        ProcessRequestLock::whereIn('process_request_id', $requestIds)->delete();

        $bar = $this->output->createProgressBar($tasks->count());

        $bar->start();

        foreach ($tasks as $token) {
            $bar->advance();
            $instance = $token->processRequest;
            $process = $instance->process;
            RunScriptTask::dispatch($process, $instance, $token, []);
        }

        $bar->finish();
    }

    private function retrieveTaskList()
    {
        $tasks = ProcessRequestToken::whereIn('status', array('FAILING', 'ACTIVE'))->where('element_type', 'scriptTask');

        if ($this->option('process') && $this->option('request')) {
            exit($this->error('Please specify either a Process ID or a Request ID, not both.'));
        }

        if (!$this->option('process') && !$this->option('request')) {
            $this->line("\nThis will retry *all* failing script tasks. It is recommended to specify a Process ID or Request ID.");
            if (!$this->confirm('Are you sure you wish to continue?')) {
                exit;
            }
        }

        if ($this->option('process')) {
            $tasks = $tasks->where('process_id', $this->option('process'));
        }

        if ($this->option('request')) {
            $tasks = $tasks->where('process_request_id', $this->option('request'));
        }

        return $tasks->get();
    }
}
