<?php

namespace ProcessMaker\WebServices;

use ProcessMaker\Contracts\TemplateExpressionInterface;
use ProcessMaker\Models\FeelExpressionEvaluator;
use ProcessMaker\Models\MustacheExpressionEvaluator;

class ExpressionEvaluator
{
    public static function evaluate($expressionType, $expression, $data)
    {

        return self::getEvaluator($expressionType)->render($expression, $data);
    }

    private static function getEvaluator($type) : TemplateExpressionInterface
    {
        switch ($type) {
            case 'mustache':
                return new MustacheExpressionEvaluator();
            case 'feel':
                return new FeelExpressionEvaluator();
        }
    }
}