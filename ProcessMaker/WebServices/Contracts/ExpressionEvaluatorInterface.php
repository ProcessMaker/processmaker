<?php

namespace ProcessMaker\WebServices\Contracts;

interface ExpressionEvaluatorInterface
{
    public function evaluate($expression, $data);
}