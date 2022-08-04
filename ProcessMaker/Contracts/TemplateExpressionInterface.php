<?php

namespace ProcessMaker\Contracts;

/**
 * All expression evaluators like the Mustache evaluator implement this interface
 */
interface TemplateExpressionInterface
{
    /**
     * @param  string  $template  template used by the template engine
     * @param  string  $data array of data used by the template engine
     * @return string evaluated string
     */
    public function render($template, $data);
}
