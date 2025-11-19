<?php

namespace Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SqsDispatchOverrideTest extends TestCase
{
    #[Test]
    public function it_forces_default_queue_when_using_sqs()
    {
        // Simulate SQS connection
        Config::set('queue.default', 'sqs');

        Queue::fake();

        // Dispatch a job with a custom queue name
        FakeJob::dispatch()->onQueue('bpmn');

        // Assert the job was dispatched to the forced queue (default)
        Queue::assertPushed(FakeJob::class, function ($job) {
            return $job->queue === null;
        });
    }

    #[Test]
    public function it_respects_original_queue_when_not_using_sqs()
    {
        // Simulate Redis connection
        Config::set('queue.default', 'redis');

        Bus::fake();

        // Dispatch a job with a custom queue name
        FakeJob::dispatch()->onQueue('bpmn');

        Bus::assertDispatched(FakeJob::class, function ($job) {
            return $job->queue === 'bpmn';
        });
    }
}

class FakeJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        \Log::info('TestJob executed successfully.');
    }
}
