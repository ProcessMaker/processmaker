<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Arr;
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

    private static $pmFunctions = [];

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
        $this->registerPMFunctions();
    }

    /**
     * Register a custom PM function
     *
     * @param callable $callable
     */
    public function registerPMFunction($name, callable $callable)
    {
        static::$pmFunctions[$name] = $callable;
    }

    /**
     * Register system functions
     *
     */
    private function registerPMFunctions()
    {
        $this->feelExpression->register(
            'getActiveTaskAt',
            function () {
            },
            function ($arguments, $element_id, $request_id) {
                $task = ProcessRequestToken::where('element_id', $element_id)
                    ->where('process_request_id', $request_id)
                    ->where('status', 'ACTIVE')
                    ->first();
                return $task;
            }
        );
        // Escalate a task to the assigned user's manager
        $this->feelExpression->register(
            'escalateTask',
            function () {
            },
            function ($arguments, $task_id) {
                $task = ProcessRequestToken::find($task_id);
                $task->reassignTo('#manager')->save();
            }
        );
        // Escalate a task to the assigned user's delegation user
        $this->feelExpression->register(
            'delegateTask',
            function () {
            },
            function ($arguments, $task_id) {
                $task = ProcessRequestToken::find($task_id);
                if ($task->user) {
                    $delegationUserId = $task->user->delegation_user_id;
                    $task->reassignTo($delegationUserId)->save();
                }
            }
        );
        $this->feelExpression->register(
            'reassignTasks',
            function () {
            },
            function ($arguments, $user_id, $target_user_id) {
                $activeTasks = ProcessRequestToken::where('user_id', $user_id)
                    ->where('element_type', 'task')
                    ->where('status', 'ACTIVE')
                    ->get();
                foreach ($activeTasks as $task) {
                    $task->reassignTo($target_user_id)->save();
                }
                return $target_user_id;
            }
        );
        $this->feelExpression->register(
            'get',
            function () {
            },
            function ($arguments, $o, $a) {
                return ((array)$o)[$a];
            }
        );
        // date($format, $timestamp)
        $this->feelExpression->register(
            'date',
            function () {
            },
            function ($arguments, $a, $b = null) {
                return date($a, $b ?: time());
            }
        );
        // empty($name)
        $this->feelExpression->register(
            'empty',
            function () {
            },
            function ($data, $name) {
                return empty($data[$name]);
            }
        );
        // env($name)
        $this->feelExpression->register(
            'env',
            function () {
            },
            function ($__data, $name) {
                $env = EnvironmentVariable::where('name', $name)->first();
                if ($env) {
                    return $env->value;
                }
                return env($name);
            }
        );
        // process($id)
        $this->feelExpression->register(
            'process',
            function () {
            },
            function ($__data, $id) {
                return Process::find($id);
            }
        );
        // request($id)
        $this->feelExpression->register(
            'request',
            function () {
            },
            function ($__data, $id) {
                return ProcessRequest::find($id);
            }
        );
        // user($id)
        $this->feelExpression->register(
            'user',
            function () {
            },
            function ($__data, $id) {
                return User::find($id);
            }
        );
        // lowercase($str)
        $this->feelExpression->register(
            'lowercase',
            function () {
            },
            function ($__data, $str) {
                return strtolower($str);
            }
        );
        // uppercase($str)
        $this->feelExpression->register(
            'uppercase',
            function () {
            },
            function ($__data, $str) {
                return strtoupper($str);
            }
        );

        // arrayget($array, $key, $default)
        // similar to laravel's Arr:get: arrayget($myarray, 'field1.subfield2', false)
        $this->feelExpression->register(
            'arrayget',
            function () {
            },
            function ($arguments, $array, $key, $default) {
                return Arr::get($array, $key, $default);
            }
        );

        // Register global PM functions from packages
        foreach (static::$pmFunctions as $name => $callable) {
            $this->feelExpression->register(
                $name,
                function () {
                },
                $callable
            );
        }
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
        } else {
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
        return $this->evaluate($this->getTemplateEngine(), $this->getBody(), $data);
    }

    /**
     *  Evaluate an expression using an specific template engine
     *
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
        $values = $encoder ? $this->$encoder($data) : $data;
        try {
            return $this->$evaluator->evaluate($body, $values);
        } catch (SyntaxError $syntaxError) {
            throw new SyntaxErrorException($syntaxError, $body, $values);
        } catch (Throwable $error) {
            throw new ExpressionFailedException($error);
        }
    }
}
