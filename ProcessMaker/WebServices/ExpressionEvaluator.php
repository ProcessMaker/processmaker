<?php

namespace ProcessMaker\WebServices;

use ProcessMaker\Contracts\TemplateExpressionInterface;
use ProcessMaker\Exception\ExpressionFailedException;
use ProcessMaker\Models\FeelExpressionEvaluator;
use ProcessMaker\Models\MustacheExpressionEvaluator;

class ExpressionEvaluator
{
    public static function evaluate($expressionType, $expression, $data)
    {
        return self::getEvaluator($expressionType)->render($expression, $data);
    }

    /**
     * @throws ExpressionFailedException
     */
    private static function getEvaluator($type) : TemplateExpressionInterface
    {
        switch ($type) {
            case 'mustache':
                return new MustacheExpressionEvaluator();
            case 'feel':
                return new FeelExpressionEvaluator();
        }

        throw new ExpressionFailedException("Expression evaluator of type '$type' is not supported.");
    }
}
