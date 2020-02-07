<?php

namespace ProcessMaker;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Nayra\Contracts\Engine\EngineInterface;
use ProcessMaker\Nayra\Contracts\EventBusInterface;
use ProcessMaker\Nayra\Contracts\RepositoryInterface;
use ProcessMaker\Nayra\Engine\EngineTrait;

/**
 * Test implementation for EngineInterface.
 *
 * @package ProcessMaker
 */
class BpmnEngine implements EngineInterface
{
    use EngineTrait;

    /**
     * @var RepositoryFactoryInterface
     */
    private $repository;

    /**
     * @var EventBusInterface $dispatcher
     */
    private $dispatcher;

    /**
     * Process definition row.
     *
     * @var \ProcessMaker\Model\Process
     */
    private $process;

    /**
     * Loaded versioned definitions
     */
    private $definitions = [];

    /**
     * Test engine constructor.
     *
     * @param RepositoryInterface $repository
     * @param EventBusInterface $dispatcher
     */
    public function __construct(RepositoryInterface $repository, $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return EventBusInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param EventBusInterface $dispatcher
     *
     * @return $this
     */
    public function setDispatcher(EventBusInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * @return RepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param RepositoryInterface $repository
     *
     * @return $this
     */
    public function setRepository(RepositoryInterface $repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @return Model\Process
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param RepositoryInterface $repository
     *
     * @return $this
     */
    public function setProcess(Process $process)
    {
        $this->process = $process;
        return $this;
    }

    /**
     * Load an execution instance from an external storage.
     *
     * @param ProcessRequest $instance
     *
     * @return ExecutionInstanceInterface|null
     */
    public function loadProcessRequest(ProcessRequest $instance)
    {
        // If exists return the already loaded instance by id
        foreach ($this->executionInstances as $executionInstance) {
            if ($executionInstance->getId() === $instance->getKey()) {
                return $executionInstance;
            }
        }
        $definitions = $this->getDefinition($instance->processVersion ?? $instance->process);
        $instance = $this->loadExecutionInstance($instance->getKey(), $definitions);
        return $instance;
    }

    public function getDefinition($processVersion)
    {
        $key = $processVersion->getKey();
        if(!isset($this->definitions[$key])) {
            $this->definitions[$key]  = $processVersion->getDefinitions(false, $this);
        }
        return $this->definitions[$key];
    }
}
