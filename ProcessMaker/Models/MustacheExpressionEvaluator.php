<?php

namespace ProcessMaker\Models;

use Mustache_Engine;
use ProcessMaker\Contracts\TemplateExpressionInterface;
use Throwable;

/**
 * Class MustacheExpressionEvaluator
 * @package ProcessMaker\Models
 */
class MustacheExpressionEvaluator implements TemplateExpressionInterface
{
    private $engine;

    public function __construct()
    {
        $this->engine = new Mustache_Engine();
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
            $result = $this->engine->render($template, $data);
        }
        catch (\Exception $ex) {
            $result = '';
        }

        return $result;
    }
}
