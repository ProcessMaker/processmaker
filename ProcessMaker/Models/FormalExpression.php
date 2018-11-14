<?php

namespace ProcessMaker\Models;

use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Nayra\Bpmn\BaseTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * FormalExpression
 *
 * @package ProcessMaker\Model
 */
class FormalExpression implements FormalExpressionInterface
{

    use BaseTrait;

    /**
     * Languages supported for expressions
     */
    const languages = [
        'FEEL' => 'feelExpression',
    ];

    const defaultLanguage = 'FEEL';

    /**
     * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage $expressionLanguage
     */
    private $feelExpression;

    /**
     * Initialize the expression language evaluator
     */
    private function initFormalExpression()
    {
        $this->feelExpression = new ExpressionLanguage();
    }

    /**
     * Evaluate the format expression.
     *
     * @param array $data
     *
     * @return string
     */
    private function evaluate(array $data)
    {
        $language = $this->getLanguage() ?: self::defaultLanguage;
        if (!in_array($language, self::languages)) {
            throw new ScriptLanguageNotSupported($language);
        }
        $evaluator = self::languages[$language];
        return $this->$evaluator->evaluate($this->getBody(), $data);
    }

    /**
     * Get the body of the Expression.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_BODY);
    }

    /**
     * Get the type that this Expression returns when evaluated.
     *
     * @return string
     */
    public function getEvaluatesToType()
    {
        return $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_EVALUATES_TO_TYPE_REF);
    }

    /**
     * Get the expression language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_LANGUAGE);
    }

    /**
     * Invoke the format expression.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function __invoke($data)
    {
        return $this->evaluate($data);
    }
}
