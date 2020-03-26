<?php

namespace ProcessMaker\Contracts;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

/**
 * All expression evaluators like the Mustache evaluator implement this interface
 * @package ProcessMaker\Contracts
 */
interface TemplateExpressionInterface
{
    /**
     * @param string $template  template used by the template engine
     * @param string $data array of data used by the template engine
     * @return string evaluated string
     */
    public function render($template, $data);
}
