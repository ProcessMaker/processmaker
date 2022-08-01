<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\Process;

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
            $this->info('Process: '.$process->name);
            $process->warnings = [];
            if (! $process->validateBpmnDefinition()) {
                // Save validation errors
                $process->save();
            }
        }
    }
}
