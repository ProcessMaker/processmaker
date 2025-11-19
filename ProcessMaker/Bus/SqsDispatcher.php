<?php

namespace ProcessMaker\Bus;

use Illuminate\Bus\Dispatcher as BaseDispatcher;

class SqsDispatcher extends BaseDispatcher
{
    public function dispatch($command)
    {
        if (config('queue.default') === 'sqs' && property_exists($command, 'queue')) {
            // Override to the SQS default queue
            $command->queue = null;

            \Log::info('Forcing queue to default for job: ' . get_class($command));
        }

        return parent::dispatch($command);
    }
}
