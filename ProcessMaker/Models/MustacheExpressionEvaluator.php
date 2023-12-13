<?php

namespace ProcessMaker\Models;

use Mustache_Engine;
use ProcessMaker\Contracts\TemplateExpressionInterface;
use Throwable;

/**
 * Class MustacheExpressionEvaluator
 */
class MustacheExpressionEvaluator implements TemplateExpressionInterface
{
    private $engine;

    public function __construct(array $options = [])
    {
        $this->engine = new Mustache_Engine($options);
    }

    /**
     * Evaluates the template using the mustache engine, if some error is generated an empty string
     * is returned
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function render($template, $data)
    {
        try {
            $result = $this->engine->render($template, $data);
        } catch (\Exception $ex) {
            $result = '';
        }

        return $result;
    }

    public function getEngine(): Mustache_Engine
    {
        return $this->engine;
    }
}
