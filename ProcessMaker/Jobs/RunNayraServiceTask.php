<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class RunNayraServiceTask implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    public $tokenId;

    public $attemptNum = 1;
    
    /**
     * Create a new job instance.
     *
     * @param \ProcessMaker\Models\ProcessRequestToken $token
     * @param array $data
     */
    public function __construct(TokenInterface $token)
    {
        $this->tokenId = $token->getKey();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get token
        $token = ProcessRequestToken::find($this->tokenId);
        $token->loadTokenProperties();
        $instance = $token->processRequest;
        $instance->loadProcessRequestInstance();
        $token->setInstance($instance);

        // Run service task
        WorkflowManager::handleServiceTask($token, $this);
    }
}
