<?php
namespace ProcessMaker\Repositories;

use ProcessMaker\Model\Application as Instance;
use ProcessMaker\Model\FormalExpression;
use ProcessMaker\Nayra\Contracts\Repositories\StorageInterface;
use ProcessMaker\Nayra\Contracts\RepositoryInterface;
use ProcessMaker\Nayra\RepositoryTrait;
use ProcessMaker\Repositories\ExecutionInstanceRepository;
use ProcessMaker\Repositories\TokenRepository;
use ProcessMaker\Model\DataStore;

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
        
    }

    public function createExecutionInstanceRepository()
    {
        return new ExecutionInstanceRepository();
    }

    public function createFormalExpression()
    {
        return new FormalExpression();
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
    
    public function createDataStore() {
        return new DataStore();
    }
}
