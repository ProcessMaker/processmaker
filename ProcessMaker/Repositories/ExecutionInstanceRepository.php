<?php

namespace ProcessMaker\Repositories;

use ProcessMaker\Model\Application as Instance;
use ProcessMaker\Nayra\Bpmn\RepositoryTrait;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;

/**
 * Execution Instance Repository.
 *
 * @package ProcessMaker\Models
 */
class ExecutionInstanceRepository implements ExecutionInstanceRepositoryInterface
{
    use RepositoryTrait;

    /**
     * Create an execution instance.
     *
     * @return \ProcessMaker\Instancex
     */
    public function createExecutionInstance()
    {
        //@todo Remove from Interface?
    }

    /**
     * Load an execution instance from a persistent storage.
     *
     * @param string $uid
     *
     * @return \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface
     */
    public function loadExecutionInstanceByUid($uid)
    {
        $instance = Instance::where('uid', $uid)->first();
        if (!$instance) return;
        $callableId = $instance->callable;
        $process = $this->getStorage()->getProcess($callableId);
        $dataStore = $this->getStorage()->getFactory()->createDataStore();
        $dataStore->setData(json_decode($instance->APP_DATA, true));
        $instance->setProcess($process);
        $instance->setDataStore($dataStore);
        $process->getTransitions($this->getStorage()->getFactory());

        //Load tokens:
        foreach($instance->delegations as $token) {
            $tokenInfo = [
                'id' => $token->uid,
                'status' => $token->thread_status,
                'element_ref' => $token->element_ref,
            ];
            $token->setProperties($tokenInfo);
            $element = $this->getStorage()->getElementInstanceById($tokenInfo['element_ref']);
            $element->addToken($instance, $token);
        }
        return $instance;
    }

    /**
     * Create or update an execution instance to a persistent storage.
     *
     * @param \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface $instance
     *
     * @return $this
     */
    public function storeExecutionInstance(ExecutionInstanceInterface $instance)
    {
        // TODO: Implement store() method. or Remove from Interface
    }
}
