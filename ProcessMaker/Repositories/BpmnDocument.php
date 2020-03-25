<?php

namespace ProcessMaker\Repositories;

use ProcessMaker\Contracts\ProcessModelInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument as StorageBpmnDocument;

class BpmnDocument extends StorageBpmnDocument
{
    /**
     * Process model
     *
     * @var ProcessModelInterface
     */
    private $model;

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
}
