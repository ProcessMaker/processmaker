<?php

namespace Tests\Feature\Api;

use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Jobs\StartEventConditional;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\Process;
use Tests\TestCase;

class ConditionalStartEventTest extends TestCase
{
    public function testConditionalEventMustTriggeredWhenActive()
    {
        //Create a conditional process with ACTIVE status by default
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../../Fixtures/conditional_event_process.json')
        );

        //Evaluates that StartEventConditional is triggering
        $this->expectsJobs(StartEventConditional::class);

        $manager = new TaskSchedulerManager();
        $manager->evaluateConditionals();
    }

    public function testConditionalEventMustNotTriggeredWhenInactive()
    {
        //Create a conditional process with ACTIVE status by default
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../../Fixtures/conditional_event_process.json')
        );

        //Get created process and set status to INACTIVE
        $process = Process::orderBy('id', 'desc')->first();
        $process->status = 'INACTIVE';
        $process->save();

        //Evaluates that StartEventConditional is NOT triggering because process is INACTIVE
        $this->doesntExpectJobs(StartEventConditional::class);

        $manager = new TaskSchedulerManager();
        $manager->evaluateConditionals();
    }
}
