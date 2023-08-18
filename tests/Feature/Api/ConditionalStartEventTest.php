<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Bus;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Jobs\StartEventConditional;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\Process;
use Tests\TestCase;

class ConditionalStartEventTest extends TestCase
{
    public function testConditionalEventMustTriggeredWhenActive()
    {
        Bus::fake([StartEventConditional::class]);

        //Create a conditional process with ACTIVE status by default
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../../Fixtures/conditional_event_process.json')
        );

        $manager = new TaskSchedulerManager();
        $manager->evaluateConditionals();

        //Evaluates that StartEventConditional is triggering
        Bus::assertDispatched(StartEventConditional::class);
    }

    public function testConditionalEventMustNotTriggeredWhenInactive()
    {
        Bus::fake([StartEventConditional::class]);

        //Create a conditional process with ACTIVE status by default
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../../Fixtures/conditional_event_process.json')
        );

        //Get created process and set status to INACTIVE
        $process = Process::orderBy('id', 'desc')->first();
        $process->status = 'INACTIVE';
        $process->save();

        $manager = new TaskSchedulerManager();
        $manager->evaluateConditionals();

        //Evaluates that StartEventConditional is NOT triggering because process is INACTIVE
        Bus::assertNotDispatched(StartEventConditional::class);
    }
}
