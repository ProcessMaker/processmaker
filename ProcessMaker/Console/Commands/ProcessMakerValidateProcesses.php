<?php

namespace ProcessMaker\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use ProcessMaker\Jobs\TestStatusJob;
use ProcessMaker\Mail\TestStatusEmail;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use Throwable;

class ProcessMakerValidateProcesses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:validate_processes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute BPMN validations for all processes';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Starting ProcessMaker Validate Processes...');
        $processes = Process::nonSystem()->get();
        foreach ($processes as $process) {
            $this->info('Process: ' . $process->name);
            $process->warnings = [];
            if (!$process->validateBpmnDefinition()) {
                // Save validation errors
                $process->save();
            }
        }
    }
}
