<?php

namespace ProcessMaker\Models;

use Mustache_Engine;
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
class FormalExpression implements FormalExpressionInterface
{
    use BaseTrait;

    const defaultLanguage = 'FEEL';

    const templateEngine = 'Mustache';

    /**
     * Languages supported for expressions
     */
    const languages = [
        'FEEL' => ['feelExpression', 'feelEncode'],
    ];


    /**
     * FEEL expression object to be used to evaluate
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
     * Prepare the data for the FEEL evaluator
     *
     * @param array $data
     *
     * @return array
     */
    private function feelEncode(array $data)
    {
        return (array) json_decode(json_encode($data));
    }

    private function getTemplateEngine()
    {
        if (self::templateEngine == 'Mustache') {
            return new MustacheExpressionEvaluator();
        }
        else {
            throw new \Exception("Template engine not supported");
        }
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
     * Get the body of the Expression.
     *
     * @param string $body
     *
     * @return $this
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
     * Get the expression language.
     *
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->setProperty(FormalExpressionInterface::BPMN_PROPERTY_LANGUAGE, $language);
        return $this;
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
        return $this->evaluate($this->getTemplateEngine(), $this->getBody(),$data);
    }

    /**
     *  Evaluate an expression using an specific template engine

     * @param \ProcessMaker\Contracts\TemplateExpressionInterface $templateEngine
     * @param string $expression
     * @param array $data
     *
     * @return bool
     *
     * @throws ExpressionFailedException
     * @throws ScriptLanguageNotSupported
     * @throws SyntaxErrorException
     */
    private function evaluate($templateEngine, $expression, array $data)
    {
        $body = $templateEngine->render($expression, $data);

        if (!trim($body)) {
            return true;
        }

        $language = $this->getLanguage() ?: self::defaultLanguage;
        if (!isset(self::languages[$language])) {
            throw new ScriptLanguageNotSupported($language);
        }
        $evaluator = self::languages[$language][0];
        $encoder = isset(self::languages[$language][1]) ? self::languages[$language][1] : null;
        try {
            $values = $encoder ? $this->$encoder($data) : $data;
            return $this->$evaluator->evaluate($body, $values);
        } catch (SyntaxError $syntaxError) {
            throw new SyntaxErrorException($syntaxError, $body);
        } catch (Throwable $error) {
            throw new ExpressionFailedException($error);
        }
    }
}
