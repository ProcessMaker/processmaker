<?php

namespace ProcessMaker\Repositories;

use ProcessMaker\Contracts\ProcessModelInterface;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Nayra\Storage\BpmnDocument as StorageBpmnDocument;

class BpmnDocument extends StorageBpmnDocument
{
    /**
     * Process model
     *
     * @var ProcessModelInterface
     */
    private $model;

    protected ProcessVersion $processVersion;

    public function __construct(ProcessModelInterface $model)
    {
        $this->setModel($model);
        parent::__construct();
    }

    /**
     * Get process model
     *
     * @return  ProcessModelInterface
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set process model
     *
     * @param  ProcessModelInterface  $model  Process model
     *
     * @return  self
     */
    public function setModel(ProcessModelInterface $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the value of processVersion
     *
     * @return ProcessVersion
     */
    public function getProcessVersion(): ProcessVersion
    {
        return $this->processVersion;
    }

    /**
     * Set the value of processVersion
     *
     * @param ProcessVersion $processVersion Process version
     *
     * @return BpmnDocument
     */
    public function setProcessVersion(ProcessVersion $processVersion): self
    {
        $this->processVersion = $processVersion;

        return $this;
    }
}
