<?php

namespace ProcessMaker\Models;

use ProcessMaker\Nayra\Bpmn\ActivitySubProcessTrait;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityClosedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ErrorEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowInterface;

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
            function ($self, TokenInterface $token, FlowInterface $sequenceFlow) {
                $callable = $this->getCalledElement();
                // Capability to specify the target start event on the sequence flow to the call activity.
                $startId = $sequenceFlow->getProperty('startEvent');
                $startEvent = $startId ? $callable->getEngine()->getStorage()->getElementInstanceById($startId) : null;
                $dataStore = $callable->getRepository()->createDataStore();
                // The entire data model is sent to the target
                $data = $token->getInstance()->getDataStore()->getData();

                // Add info about parent
                $data['_parent'] = [
                    'process_id' => $token->getInstance()->process_id,
                    'request_id' => $token->getInstance()->id,
                    'node_id' => $token->element_id,
                ];

                $configString = $this->getProperty('config');
                if ($configString) {
                    $data['_parent']['config'] = json_decode($configString, true);
                }

                $dataStore->setData($data);
                $instance = $callable->call($dataStore, $startEvent);
                $this->linkProcesses($token, $instance);
                $this->syncronizeInstances($token->getInstance(), $instance);
                $this->getRepository()
                    ->getTokenRepository()
                    ->persistCallActivityActivated($token, $instance, $sequenceFlow);
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
                        // Copy data from subprocess to main process
                        $dataStore = $token->getInstance()->getDataStore();
                        $data = $closedInstance->getDataStore()->getData();
                        foreach ($data as $key => $value) {
                            $dataStore->putData($key, $value);
                        }
                        $this->syncronizeInstances($instance, $token->getInstance());
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
        $calledElementRef = $this->getProperty(CallActivityInterface::BPMN_PROPERTY_CALLED_ELEMENT);
        $refs = explode('-', $calledElementRef);
        if (count($refs) === 1) {
            $localBpmn = $this->ownerProcess->getEngine()->getStorage();
            return $localBpmn->getElementInstanceById($calledElementRef);
        } elseif (count($refs) === 2) {
            // Capability to reuse other processes inside a process
            $process = Process::findOrFail($refs[1]);
            return isset($this->getProcess()->getEngine()->currentInstance) ? $this->getProcess()->getEngine()->currentInstance->getProcess()
                : $process->getDefinitions()->getElementInstanceById($refs[0]);
        }
    }

    /**
     * Set the called element by the activity.
     *
     * @param \ProcessMaker\Nayra\Contracts\Bpmn\CallableElementInterface|string $callableElement
     *
     * @return $this
     */
    public function setCalledElement($callableElement)
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

    /**
     * Syncronize two process instances
     *
     * @param ExecutionInstanceInterface $instance
     * @param ExecutionInstanceInterface $currentInstance
     */
    private function syncronizeInstances(ExecutionInstanceInterface $instance, ExecutionInstanceInterface $currentInstance)
    {
        if ($instance->process->id !== $currentInstance->process->id) {
            $currentInstance->getProcess()->getEngine()->runToNextState();
        }
    }
}
