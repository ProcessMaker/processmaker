<?php

namespace ProcessMaker\Models;

use ProcessMaker\Nayra\Bpmn\ActivitySubProcessTrait;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityClosedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ErrorInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowInterface;
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
        completeSubprocess as completeSubprocessBase;
        catchSubprocessError as catchSubprocessErrorBase;
    }

    /**
     * Initialize the Call Activity element.
     *
     */
    protected function initActivity()
    {
        $this->attachEvent(
            ActivityInterface::EVENT_ACTIVITY_ACTIVATED,
            function ($self, TokenInterface $token, FlowInterface $sequenceFlow) {
                $instance = $this->callSubprocess($token, $sequenceFlow);
                $this->getRepository()
                    ->getTokenRepository()
                    ->persistCallActivityActivated($token, $instance, $sequenceFlow);
                $this->linkProcesses($token, $instance);
                $this->syncronizeInstances($token->getInstance(), $instance);
            }
        );
    }

    /**
     * Call the subprocess
     *
     * @return ExecutionInstanceInterface
     */
    protected function callSubprocess(TokenInterface $token, FlowInterface $sequenceFlow)
    {
        $callable = $this->getCalledElement();
        // Capability to specify the target start event on the sequence flow to the call activity.
        $startId = $sequenceFlow->getProperty('startEvent');
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
            $config = json_decode($configString, true);
            $data['_parent']['config'] = $config;
            if (isset($config['startEvent'])) {
                $startId = $config['startEvent'];
            }
        }

        $startEvent = $startId ? $callable->getOwnerDocument()->getElementInstanceById($startId) : null;

        $dataStore->setData($data);
        $instance = $callable->call($dataStore, $startEvent);
        return $instance;
    }

    /**
     * Complete the subprocess
     *
     * @param TokenInterface $token
     * @param ExecutionInstanceInterface $closedInstance
     * @param ExecutionInstanceInterface $instance
     *
     * @return CallActivity
     */
    protected function completeSubprocess(TokenInterface $token, ExecutionInstanceInterface $closedInstance, ExecutionInstanceInterface $instance)
    {
        $this->completeSubprocessBase($token);
        // Copy data from subprocess to main process
        $dataStore = $token->getInstance()->getDataStore();
        $data = $closedInstance->getDataStore()->getData();
        foreach ($data as $key => $value) {
            $dataStore->putData($key, $value);
        }
        $this->syncronizeInstances($instance, $token->getInstance());
        return $this;
    }

    /**
     * Catch a subprocess error
     *
     * @param TokenInterface $token
     * @param ErrorInterface|null $error
     * @param ExecutionInstanceInterface $instance
     *
     * @return CallActivity
     */
    protected function catchSubprocessError(TokenInterface $token, ErrorInterface $error = null, ExecutionInstanceInterface $instance)
    {
        $this->catchSubprocessErrorBase($token, $error);
        $this->syncronizeInstances($instance, $token->getInstance());
        return $this;
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
            return $this->getOwnerDocument()->getElementInstanceById($calledElementRef);
        } elseif (count($refs) === 2) {
            // Capability to reuse other processes inside a process
            $process = Process::findOrFail($refs[1]);
            $engine = $this->getProcess()->getEngine();
            $definitions = $engine->getDefinition($process->getLatestVersion());
            $response = $definitions->getElementInstanceById($refs[0]);
            return $response;
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
            $subprocess = $this->getProcess()->getEngine()->loadProcessRequest($token->subProcessRequest);
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
