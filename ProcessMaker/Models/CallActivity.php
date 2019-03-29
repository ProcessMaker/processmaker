<?php

namespace ProcessMaker\Models;

use ProcessMaker\Nayra\Bpmn\ActivitySubProcessTrait;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityClosedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Nayra\Bpmn\Models\Activity;
use ProcessMaker\Nayra\Bpmn\State;
use ProcessMaker\Nayra\Bpmn\Transition;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallableElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ErrorEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\GatewayInterface;
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

        $this->attachEvent(
            ActivityInterface::EVENT_ACTIVITY_CLOSED,
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


    /**
     * Get an input to the element.
     *
     * @return StateInterface
     */
    public function getInputPlace()
    {

        $ready = new State($this);
        $transition = new Transition($this, false);
        $ready->connectTo($transition);
        $transition->connectTo($this->activeState);
        $this->addInput($ready);

        $ready->attachEvent(State::EVENT_TOKEN_ARRIVED, function (TokenInterface $token) {
//            if ($source->process_collaboration_id === null) {



                $collaboration = new ProcessCollaboration();
                $collaboration->process_id = $this->getOwnerProcess()->getEngine()->getProcess()->id;
                $collaboration->saveOrFail();



                $token->getInstance()-> process_collaboration_id = $collaboration->getKey();
                $token->getInstance()->saveOrFail();
//            }
//            $instance->process_collaboration_id = $source->process_collaboration_id;
//            $instance->participant_id = $participant ? $participant->getId() : null;
//            $instance->saveOrFail();




//            $definitions=$this->getOwnerProcess()->getEventDefinitions();
//            $collaboration = $this->getEventDefinitions()->item(0)->getPayload()->getMessageFlow()->getCollaboration();
//            $collaboration->send($this->getEventDefinitions()->item(0), $token);
//
//            $this->getRepository()
//                ->getTokenRepository()
//                ->persistThrowEventTokenArrives($this, $token);
//
//            $this->notifyEvent(ActivityInterface::EVENT_ACTIVITY_ACTIVATED, $this, $token);
        });

        return $ready;

        $incomingPlace=new State($this, GatewayInterface::TOKEN_STATE_INCOMING);
        $incomingPlace->connectTo($this->transition);
        $incomingPlace->attachEvent(State::EVENT_TOKEN_ARRIVED, function (TokenInterface $token) {
            $collaboration = $this->getEventDefinitions()->item(0)->getPayload()->getMessageFlow()->getCollaboration();
            $collaboration->send($this->getEventDefinitions()->item(0), $token);

            $this->getRepository()
                ->getTokenRepository()
                ->persistThrowEventTokenArrives($this, $token);

//            $this->notifyEvent(IntermediateThrowEventInterface::EVENT_THROW_TOKEN_ARRIVES, $this, $token);
            $this->notifyEvent(ActivityInterface::EVENT_ACTIVITY_ACTIVATED, $this, $token);
        });

        $incomingPlace->attachEvent(State::EVENT_TOKEN_CONSUMED, function (TokenInterface $token) {

            $this->getRepository()
                ->getTokenRepository()
                ->persistActivityClosed($this, $token);

//            $this->notifyEvent(CallEve::EVENT_THROW_TOKEN_CONSUMED, $this, $token);
            $this->notifyEvent(Activity::EVENT_ACTIVITY_CLOSED, $this, $token);
        });

        return $incomingPlace;
    }

//    protected function buildConnectionTo(FlowNodeInterface $target)
//    {
//        $place = $target->getInputPlace();
//        $this->transition->connectTo($place);
//        $place->attachEvent(
//            StateInterface::EVENT_TOKEN_CONSUMED,
//            function (TokenInterface $token) {
//                $token->setStatus(ActivityInterface::TOKEN_STATE_CLOSED);
//                $this->getRepository()
//                    ->getTokenRepository()
//                    ->persistActivityClosed($this, $token);
//                $this->notifyEvent(ActivityInterface::EVENT_ACTIVITY_CLOSED, $this, $token);
//
//            }
//        );
//        return $this;
//    }

}
