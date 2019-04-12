<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use ProcessMaker\Models\ProcessRequest;

class TerminateRequest extends BpmnAction implements ShouldQueue
{
    public $definitionsId;
    public $instanceId;
    public $tokenId;
    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ProcessRequest $instance)
    {
        $this->definitionsId = $instance->process()->first()->getKey();
        $this->instanceId = $instance->getKey();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function action(ProcessRequest $instance)
    {
        // Close all active tokens
        $instance->close();
        // Close process request
        $instance->status = 'COMPLETED';
        $instance->save();
    }
}
