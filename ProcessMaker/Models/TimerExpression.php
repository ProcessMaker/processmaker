<?php

namespace ProcessMaker\Models;

use ProcessMaker\Exception\ExpressionFailedException;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Exception\SyntaxErrorException;
use ProcessMaker\Nayra\Bpmn\BaseTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Throwable;

/**
 * FormalExpression
 *
 * @package ProcessMaker\Model
 */
class TimerExpression implements FormalExpressionInterface
{

    use BaseTrait;

    /**
     * Languages supported for expressions
     */
    const languages = [
        'Timer' => ['feelExpression', 'feelEncode'],
    ];

    const defaultLanguage = 'Timer';


    /**
     * Initialize the expression language evaluator
     */
    private function initFormalExpression()
    {
//        //$this->feelExpression = new ExpressionLanguage();
//        $this->test = 'lalala';
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
        $expression = $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_BODY);
        return $this->getDateExpression()
            ?: $this->getCycleExpression()
                ?: $this->getDurationExpression();
    }

    /**
     * Get a DateTime if the expression is a date.
     *
     * @return \DateTime
     */
    protected function getDateExpression()
    {
        $expression = $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_BODY);
        try {
            $date = new \DateTime($expression);
        } catch (\Exception $e) {
            $date = false;
        }
        return $date;
    }

    /**
     * Get a DatePeriod if the expression is a cycle.
     *
     * Ex. R4/2018-05-01T00:00:00Z/PT1M
     *     R/2018-05-01T00:00:00Z/PT1M/2025-10-02T00:00:00Z
     *
     * @return \DatePeriod
     */
    protected function getCycleExpression()
    {
        $expression = $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_BODY);
        try {
            //Improve Repeating intervals (R/start/interval/end) configuration
            if (preg_match('/^R\/([^\/]+)\/([^\/]+)\/([^\/]+)$/', $expression, $repeating)) {
                $cycle = new \DatePeriod(new \DateTime($repeating[1]), new \DateInterval($repeating[2]), new \DateTime
                ($repeating[3]));
            } else {
                $cycle = new \DatePeriod($expression);
            }
        } catch (\Exception $e) {
            $cycle = false;
        }
        return $cycle;
    }

    /**
     * Get a DateInterval if the expression is a duration.
     *
     * @return \DateInterval
     */
    protected function getDurationExpression()
    {
        $expression = $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_BODY);
        try {
            $duration = new \DateInterval($expression);
        } catch (\Exception $e) {
            $duration = false;
        }
        return $duration;
    }
}
