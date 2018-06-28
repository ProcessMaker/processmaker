<?php
namespace ProcessMaker\Repositories;

use ProcessMaker\Model\Application as Instance;
use ProcessMaker\Repositories\ExecutionInstanceRepository;
use ProcessMaker\Model\Delegation as Token;
use ProcessMaker\Nayra\Contracts\Repositories\StorageInterface;
use ProcessMaker\Nayra\Contracts\RepositoryInterface;
use ProcessMaker\Nayra\RepositoryTrait;

/**
 * Definitions Repository
 *
 */
class DefinitionsRepository implements RepositoryInterface
{

    use RepositoryTrait;

    public function createExecutionInstance()
    {
        return new Instance();
    }

    public function createToken()
    {
        return new Token();
    }

    public function createCallActivity()
    {
        
    }

    public function createExecutionInstanceRepository(StorageInterface $storage)
    {
        return new ExecutionInstanceRepository($storage);
    }

    public function createFormalExpression()
    {
        
    }
}
