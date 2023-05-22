<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Notifications\ProcessCanceledNotification;
use ProcessMaker\Repositories\ExecutionInstanceRepository;
use ProcessMaker\Repositories\TokenRepository;

class CancelRequest extends BpmnAction implements ShouldQueue
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
        $this->definitionsId = $instance->process->getKey();
        $this->instanceId = $instance->getKey();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function action(ProcessRequest $instance)
    {
        //notify to the user that started the request, its cancellation
        $notifiables = $instance->getNotifiables('canceled');
        Notification::send($notifiables, new ProcessCanceledNotification($instance));

        // Close all active tokens
        $instance->close();

        // Persist closed tokens
        $tokenRepo = new TokenRepository(new ExecutionInstanceRepository());
        foreach ($instance->getTokens()->toArray() as $token) {
            $tokenRepo->store($token);
        }
    }
}
