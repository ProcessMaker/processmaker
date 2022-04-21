<?php

namespace ProcessMaker\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use ProcessMaker\Bpmn\Process;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;
use Tests\TestCase;

class GarbageCollectorTest extends TestCase
{
    public function testProcessHaltedScripts()
    {
        Bus::fake();
        //create a serve task and a script task in status ACTIVE and FAILING
        factory(ProcessRequestToken::class)->create([
            'status' => 'ACTIVE',
            'element_type' => 'scriptTask', ]);

        factory(ProcessRequestToken::class)->create([
            'status' => 'FAILING',
            'element_type' => 'serviceTask', ]);

        $all = ProcessRequestToken::all();

        // verify that we have 2 tokens
        $this->assertTrue($all->count() == 2);

        // Run the garbage collector
        $this->artisan('processmaker:garbage-collect');

        // Verify that script and service task jobs were sent
        Bus::assertDispatched(RunScriptTask::class);
        Bus::assertDispatched(RunServiceTask::class);
    }

    public function testProcessUnhandledErrors()
    {
        Bus::fake();
        // simulate the creation of 2 unhandled errors with 2 tokens
        $token1 = factory(ProcessRequestToken::class)->create([
            'status' => 'ACTIVE',
            'element_type' => 'scriptTask', ]);

        $token2 = factory(ProcessRequestToken::class)->create([
            'status' => 'FAILING',
            'element_type' => 'serviceTask', ]);

        $path = storage_path('app/private');
        $errorFile = $path.'/unhandled_error.txt';
        $this->removeFileIfExists($errorFile);

        $subscriber = new BpmnSubscriber();
        $subscriber->errorHandler($path, $token1);
        $subscriber->errorHandler($path, $token2);

        // Verify that the unhandled error file has been created
        $this->assertFileExists($errorFile);

        // Run the garbage collector
        $this->artisan('processmaker:garbage-collect');

        // Verify that a script task jobs has been sent
        Bus::assertDispatched(RunScriptTask::class);

        // Verify that the file has been deleted
        $this->assertFileNotExists($errorFile);
    }

    public function testProcessDuplicatedTimerEvents()
    {
        $token1 = factory(ProcessRequestToken::class)->create([
            'status' => 'ACTIVE',
            'element_type' => 'event', ]);

        $token2 = factory(ProcessRequestToken::class)->create([
            'process_request_id' => $token1->process_request_id,
            'status' => 'ACTIVE',
            'element_id' => $token1->element_id,
            'element_type' => 'event', ]);

        // Create duplicated scheduled tasks for the same token
        $schedule = factory(ScheduledTask::class)->create([
            'process_request_id' => $token1->process_request_id,
            'configuration' => json_encode(['node_id' => $token1->element_id]),
        ]);

        $schedule = factory(ScheduledTask::class)->create([
            'process_request_id' => $token1->process_request_id,
            'configuration' => json_encode(['node_id' => $token1->element_id]),
        ]);

        // Assert that the table was correcly created
        $this->assertNotNull(ProcessRequestToken::find($token1));
        $this->assertTrue(ScheduledTask::all()->count() === 2);

        // Run the garbage collector
        $this->artisan('processmaker:garbage-collect');

        // The duplicated schedule task should be removed
        $this->assertTrue(ScheduledTask::all()->count() === 1);
    }

    protected function generateErrorHandlerFileWith2Tokens()
    {
        $token1 = factory(ProcessRequestToken::class)->create([
            'status' => 'ACTIVE',
            'element_type' => 'scriptTask', ]);

        $token2 = factory(ProcessRequestToken::class)->create([
            'status' => 'FAILING',
            'element_type' => 'serviceTask', ]);

        $path = storage_path('app/private');
        $errorFile = $path.'/unhandled_error.txt';
        $this->removeFileIfExists($errorFile);

        $subscriber = new BpmnSubscriber();
        $subscriber->errorHandler($path, $token1);
        $subscriber->errorHandler($path, $token2);
    }

    public function removeFileIfExists(string $errorFile): void
    {
        if (file_exists($errorFile)) {
            unlink($errorFile);
        }
    }
}
