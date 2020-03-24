<?php

namespace ProcessMaker\Contracts;

use ProcessMaker\BpmnEngine;
use ProcessMaker\Repositories\BpmnDocument;

interface ProcessModelInterface
{
    /**
     * Get bpmn definitions of the process model
     *
     * @param boolean $forceParse
     * @param BpmnEngine $engine
     *
     * @return BpmnDocument
     */
    public function getDefinitions($forceParse = false, BpmnEngine $engine = null);
}
