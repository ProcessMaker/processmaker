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

/**
 * Call Activity model
 *
 * @package ProcessMaker\Model
 */
class CallActivity implements CallActivityInterface
{

    use ActivitySubProcessTrait;

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
                $this->getCalledElement()->attachEvent(
                    ProcessInterface::EVENT_PROCESS_INSTANCE_COMPLETED,
                    function ($self, $closedInstance) use($token, $instance) {
                        if ($closedInstance === $instance) {
                            if ($token->getStatus() !== ActivityInterface::TOKEN_STATE_FAILING) {
                                $token->setStatus(ActivityInterface::TOKEN_STATE_COMPLETED);
                            }
                        }
                    }
                );
                $this->getCalledElement()->attachEvent(
                    ErrorEventDefinitionInterface::EVENT_THROW_EVENT_DEFINITION,
                    function ($element, $innerToken, $errorEvent) use($token, $instance) {
                        if ($innerToken->getInstance() === $instance) {
                            $token->setStatus(ActivityInterface::TOKEN_STATE_FAILING);
                        }
                    }
                );
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
}
