<?php
namespace ProcessMaker\Repositories;

use ProcessMaker\Bpmn\Process;
use ProcessMaker\Models\CallActivity;
use ProcessMaker\Models\DataStore;
use ProcessMaker\Models\FormalExpression;
use ProcessMaker\Models\Message;
use ProcessMaker\Models\MessageEventDefinition;
use ProcessMaker\Models\TimerExpression;
use ProcessMaker\Nayra\Contracts\RepositoryInterface;
use ProcessMaker\Nayra\RepositoryTrait;

/**
 * Definitions Repository
 *
 */
class DefinitionsRepository implements RepositoryInterface
{
    use RepositoryTrait;
    
    private $tokenRepository = null;

    public function createCallActivity()
    {
        return new CallActivity();
    }

    public function createExecutionInstanceRepository()
    {
        return new ExecutionInstanceRepository();
    }

    public function createFormalExpression()
    {
        return new FormalExpression();
    }

    public function createTimerExpression()
    {
        return new TimerExpression();
    }

    /**
     * Creates a TokenRepository
     *
     * @return \ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface
     */
    public function getTokenRepository()
    {
        if ($this->tokenRepository === null) {
            $this->tokenRepository = new TokenRepository($this->createExecutionInstanceRepository());
        }
        return $this->tokenRepository;
    }
    
    public function createDataStore()
    {
        return new DataStore();
    }

    public function createMessageEventDefinition()
    {
        return new MessageEventDefinition();
    }

    public function createMessage()
    {
        return new Message();
    }

    /**
     * Create instance of Process.
     *
     * @return \ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface
     */
    public function createProcess()
    {
        $process = new Process();
        $process->setRepository($this);
        return $process;
    }
}
