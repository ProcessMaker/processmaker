<?php

namespace ProcessMaker\Models;

use Mustache_Engine;
use ProcessMaker\Contracts\TemplateExpressionInterface;
use ProcessMaker\Exception\ExpressionFailedException;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Exception\SyntaxErrorException;
use ProcessMaker\Nayra\Bpmn\BaseTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Throwable;

/**
 *
 *
 * @package ProcessMaker\Model
 */
class MustacheExpressionEvaluator implements TemplateExpressionInterface
{
    private $engine;

    public function __construct()
    {
        $this->engine = new Mustache_Engine();
    }

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
