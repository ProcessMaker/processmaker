<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Repositories\ExecutionInstanceRepository;
use ProcessMaker\Repositories\TokenRepository;

class TerminateRequestEndEvent extends BpmnAction implements ShouldQueue
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

    public function action(ProcessRequest $instance)
    {
        $instance->close();

        // Tokens are closed in memory, we need persist them
        $tokenRepo = new TokenRepository(new ExecutionInstanceRepository());
        foreach ($instance->getTokens()->toArray() as $token) {
            $tokenRepo->store($token);
        }

        $instance->status = 'COMPLETED';
        $instance->save();
    }
}
