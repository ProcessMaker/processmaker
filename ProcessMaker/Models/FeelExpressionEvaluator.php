<?php

namespace ProcessMaker\Models;

use Mustache_Engine;
use ProcessMaker\Contracts\TemplateExpressionInterface;
use Throwable;

/**
 * Class MustacheExpressionEvaluator
 * @package ProcessMaker\Models
 */
class FeelExpressionEvaluator implements TemplateExpressionInterface
{
    /**
     * Evaluates the template using the feel expression engine, if some error is generated an empty string
     * is returned
     *
     * @param string $template
     * @param string $data
     * @return string
     */
    public function render($template, $data)
    {
        try {
            $formal = new FormalExpression();
            $formal->setBody($template);
            return $formal($data);
        } catch (Exception $exception) {
            return "{$template}: " . $exception->getMessage();
        }
    }
}
