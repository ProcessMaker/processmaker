<?php

namespace ProcessMaker\Models;

use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use ProcessMaker\Nayra\Bpmn\BaseTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface;
use Mustache_Engine;

/**
 * FormalExpression to handel time events
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
        'Timer' => [],
    ];

    const defaultLanguage = 'Timer';

    /**
     * Initialize the expression language evaluator
     */
    private function initFormalExpression()
    {
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
     * Set the body of the Expression.
     *
     * @param string $body
     *
     * @return TimerExpression
     */
    public function setBody($body)
    {
        $this->setProperty(FormalExpressionInterface::BPMN_PROPERTY_BODY, $body);
        return $this;
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
        $expression = $this->mustacheTimerExpression($expression, $data);
        return $this->getDateExpression($expression)
            ?: $this->getCycleExpression($expression)
            ?: $this->getDurationExpression($expression)
            ?: $this->getMultipleCycleExpression($expression);
    }

    /**
     * Parse mustache syntax in timer expressions
     *
     * @param string $expression
     * @param array $data
     *
     * @return mixed
     */
    private function mustacheTimerExpression($expression, $data)
    {
        $mustache = new Mustache_Engine();
        return $mustache->render($expression, $data);
    }

    /**
     * Get a DateTime if the expression is a date.
     *
     * @return \DateTime
     */
    protected function getDateExpression($expression)
    {
        try {
            $date = new DateTime($expression);
        } catch (Exception $e) {
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
    protected function getCycleExpression($expression)
    {
        try {
            //Improve Repeating intervals (R/start/interval/end) configuration
            if (preg_match('/^R\/([^\/]+)\/([^\/]+)\/([^\/]+)$/', $expression, $repeating)) {
                $cycle = new DatePeriod(new DateTime($repeating[1]), new DateInterval($repeating[2]), new DateTime($repeating[3]));
            } elseif (preg_match('/^R\/([^\/]+)\/([^\/]+)$/', $expression, $repeating)) {
                $cycle = new DatePeriod(new DateTime($repeating[1]), new DateInterval($repeating[2]), -1);
            } else {
                $cycle = new DatePeriod($expression);
            }
        } catch (Exception $e) {
            $cycle = false;
        }
        return $cycle;
    }

    /**
     * Get a multiple DatePeriod if the expression is a multiple cycle.
     *
     * Ex. 2019-08-01T00:00:00Z|R4/2019-08-01T00:00:00Z/PT1W|R4/2019-08-02T00:00:00Z/PT1W
     *
     * throws every week thursday and friday
     *
     * @return array
     */
    protected function getMultipleCycleExpression($expression)
    {
        try {
            $parts = explode('|', $expression);
            $firstDate = new DateTime(array_shift($parts));
            $cycles = [];
            foreach ($parts as $part) {
                $cycles[] = $this->getCycleExpression($part);
            }
        } catch (Exception $e) {
            $cycles = false;
        }
        return $cycles;
    }

    /**
     * Get a DateInterval if the expression is a duration.
     *
     * @return \DateInterval
     */
    protected function getDurationExpression($expression)
    {
        try {
            $duration = new DateInterval($expression);
        } catch (Exception $e) {
            $duration = false;
        }
        return $duration;
    }
}
