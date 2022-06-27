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
    private $engine;

    public function __construct()
    {
        $this->engine = new FormalExpression();
    }

    /**
     * Evaluates the template using the mustache engine, if some error is generated an empty string
     * is returned
     *
     * @param string $template
     * @param string $data
     * @return string
     */
    public function render($template, $data)
    {
        try {
            $this->engine->setBody($template);
            return $this->engine($data);
        } catch (Exception $exception) {
            return "{$template}: " . $exception->getMessage();
        }
    }
}
