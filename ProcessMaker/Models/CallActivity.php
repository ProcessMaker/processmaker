<?php

namespace ProcessMaker\Models;

use ProcessMaker\Nayra\Bpmn\ActivitySubProcessTrait;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityClosedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallableElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ErrorEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

/**
 * Call Activity model
 *
 * @package ProcessMaker\Model
 */
class CallActivity implements CallActivityInterface
{
    use ActivitySubProcessTrait {
        addToken as addTokenBase;
    }

    /**
     * Configure the activity to go to a FAILING status when activated.
     *
     */
    protected function initActivity()
    {
        $this->attachEvent(
            ActivityInterface::EVENT_ACTIVITY_ACTIVATED,
            function ($self, TokenInterface $token) {
                $instance = $this->getCalledElement()->call();
                $this->getRepository()
                    ->getTokenRepository()
                    ->persistCallActivityActivated($token, $instance);
                $this->linkProcesses($token, $instance);
            }
        );
    }

    /**
     * Links parent and sub process in a CallActivity
     *
     * @param TokenInterface $token
     * @param ExecutionInstanceInterface $instance
     *
     * @return void
     */
    private function linkProcesses(TokenInterface $token, ExecutionInstanceInterface $instance)
    {
        $this->getCalledElement()->attachEvent(
            ProcessInterface::EVENT_PROCESS_INSTANCE_COMPLETED,
            function ($self, $closedInstance) use ($token, $instance) {
                if ($closedInstance->id === $instance->id) {
                    if ($token->getStatus() !== ActivityInterface::TOKEN_STATE_FAILING) {
                        $token->setStatus(ActivityInterface::TOKEN_STATE_COMPLETED);
                    }
                }
            }
        );
        $this->getCalledElement()->attachEvent(
            ErrorEventDefinitionInterface::EVENT_THROW_EVENT_DEFINITION,
            function ($element, $innerToken, $errorEvent) use ($token, $instance) {
                if ($innerToken->getInstance() === $instance) {
                    $token->setStatus(ActivityInterface::TOKEN_STATE_FAILING);
                }
            }
        );
    }

    /**
     * Array map of custom event classes for the bpmn element.
     *
     * @return array
     */
    protected function getBpmnEventClasses()
    {
        return [
            ActivityInterface::EVENT_ACTIVITY_ACTIVATED => ActivityActivatedEvent::class,
            ActivityInterface::EVENT_ACTIVITY_COMPLETED => ActivityCompletedEvent::class,
            ActivityInterface::EVENT_ACTIVITY_CLOSED => ActivityClosedEvent::class,
        ];
    }

    /**
     * Get the called element by the activity.
     *
     * @return \ProcessMaker\Nayra\Contracts\Bpmn\CallableElementInterface
     */
    public function getCalledElement()
    {
        return $this->getProperty(CallActivityInterface::BPMN_PROPERTY_CALLED_ELEMENT);
    }

    /**
     * Set the called element by the activity.
     *
     * @param \ProcessMaker\Nayra\Contracts\Bpmn\CallableElementInterface $callableElement
     *
     * @return $this
     */
    public function setCalledElement(CallableElementInterface $callableElement)
    {
        $this->setProperty(CallActivityInterface::BPMN_PROPERTY_CALLED_ELEMENT, $callableElement);
        return $this;
    }

    /**
     * Load tokens from array. And Link to the subprocess if exists.
     *
     * @param ExecutionInstanceInterface $instance
     * @param TokenInterface $token
     *
     * @return $this
     */
    public function addToken(ExecutionInstanceInterface $instance, TokenInterface $token)
    {
        if ($token->getStatus() === ActivityInterface::TOKEN_STATE_ACTIVE && !empty($token->subprocess_request_id)) {
            $subprocess = ProcessRequest::find($token->subprocess_request_id);
            $this->linkProcesses($token, $subprocess);
        }
        return $this->addTokenBase($instance, $token);
    }
}
