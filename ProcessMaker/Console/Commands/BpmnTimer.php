<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Managers\TaskSchedulerManager;

class BpmnTimer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bpmn:timer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run bpmn timers';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $scheduleManager = new TaskSchedulerManager();
        $scheduleManager->scheduleTasks();
        $scheduleManager->evaluateConditionals();
    }
}
