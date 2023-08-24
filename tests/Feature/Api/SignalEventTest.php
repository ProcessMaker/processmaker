<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Bus;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\CatchSignalEventProcess;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Jobs\RefreshArtisanCaches;
use ProcessMaker\Jobs\StartEvent;
use ProcessMaker\Jobs\ThrowSignalEvent;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\TestCase;

class SignalEventTest extends TestCase
{
    public function testSignalEventMustTriggeredWhenProcessActive()
    {
        Bus::fake([
            RefreshArtisanCaches::class,
            CatchSignalEventProcess::class,
        ]);

        //Create a signal process that TRIGGER a signal with ACTIVE status by default
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../../Fixtures/signal_event_process_trigger.json')
        );

        //Get created TRIGGER process
        $triggerProcess = Process::orderBy('id', 'desc')->first();

        //Get excluded process
        $excludedProcess = $triggerProcess->id;

        //Get signalRef
        $signalRef = $triggerProcess->signal_events[0];

        //Create a signal process that CATCH a signal with ACTIVE status by default
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../../Fixtures/signal_event_process_catcher.json')
        );

        $throwSignalEventJob = new ThrowSignalEvent($signalRef, [], [$excludedProcess], []);
        $throwSignalEventJob->handle();

        //Evaluates that CatchSignalEventProcess is triggering because catcher process is ACTIVE
        Bus::assertDispatched(CatchSignalEventProcess::class);
    }

    public function testSignalEventMustNotTriggeredWhenProcessInactive()
    {
        Bus::fake([
            RefreshArtisanCaches::class,
            CatchSignalEventProcess::class,
        ]);

        //Create a signal process that TRIGGER a signal with ACTIVE status by default
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../../Fixtures/signal_event_process_trigger.json')
        );

        //Get created TRIGGER process
        $triggerProcess = Process::orderBy('id', 'desc')->first();

        //Get excluded process
        $excludedProcess = $triggerProcess->id;

        //Get signalRef
        $signalRef = $triggerProcess->signal_events[0];

        //Create a signal process that CATCH a signal with ACTIVE status by default
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../../Fixtures/signal_event_process_catcher.json')
        );

        //Get created CATCHER process
        $catcherProcess = Process::orderBy('id', 'desc')->first();

        //Change it's status to INACTIVE
        $catcherProcess->status = 'INACTIVE';
        $catcherProcess->save();

        $throwSignalEventJob = new ThrowSignalEvent($signalRef, [], [$excludedProcess], []);
        $throwSignalEventJob->handle();

        //Evaluates that CatchSignalEventProcess is NOT triggering because catcher process is INACTIVE
        Bus::assertNotDispatched(CatchSignalEventProcess::class);
    }

    /**
     * This test verifies that in a signal start event with the "Request Variable" configured,
     * every attribute of the signal payload goes to the request
     * if payload={var1:1, var2:2}  and request data {req1:'a', req2:'b'}
     * request data should be {req1:'a', req2:'b'... request_var:{var1:1, var2:2}}
     */
    public function testSignalStarEventWithPayloadToRequestVariable()
    {
        // The process used for the tests
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../../Fixtures/signal_catch_with_payload.json')
        );

        // Payload for all the signals in the test
        $payload = ['payload1' => 1, 'payload2' => 2];

        // Dispatch the job synchronously for the test (signal 3 triggers the "Payload in variable" start event)
        ThrowSignalEvent::dispatchSync('Signal3', $payload, []);

        // Verify that the created request has the payload stored in the request variable "incoming_data"
        $createdRequestData = ProcessRequest::all()->first()->data;
        $this->assertEquals($createdRequestData['incoming_data'], $payload);
    }

    /**
     * This test verifies that in a signal start event with "Request Variable" empty,
     * every attribute of the signal payload goes to the request
     * if payload={var1:1, var2:2}  and request data {req1:'a', req2:'b'}
     * request data should be {req1:'a', req2:'b'... var1:1, var2:2}
     */
    public function testSignalStarEventWithPayloadToRequestData()
    {
        // The process used for the tests
        ImportProcess::dispatchSync(
            file_get_contents(__DIR__ . '/../../Fixtures/signal_catch_with_payload.json')
        );

        // Payload for all the signals in the test
        $payload = ['payload1' => 1, 'payload2' => 2];

        // Dispatch the job synchronously for the test
        ThrowSignalEvent::dispatchSync('Signal4', $payload, []);

        // Verify that the created request has the payload stored in the request variable "incoming_data"
        $createdRequestData = ProcessRequest::all()->first()->data;
        $this->assertArraySubset($payload, $createdRequestData);
    }
}
