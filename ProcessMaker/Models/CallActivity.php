<?php

namespace ProcessMaker\Models;

use Exception;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Nayra\Bpmn\ActivitySubProcessTrait;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityClosedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ErrorInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

/**
 * Call Activity model
 */
class CallActivity implements CallActivityInterface
{
    use ActivitySubProcessTrait {
        addToken as addTokenBase;
        completeSubprocess as completeSubprocessBase;
        catchSubprocessError as catchSubprocessErrorBase;
    }

    private $subProcessRequestVersion;

    /**
     * Initialize the Call Activity element.
     */
    protected function initActivity()
    {
        $this->attachEvent(
            ActivityInterface::EVENT_ACTIVITY_ACTIVATED,
            function (ActivityInterface $callActivity, TokenInterface $token) {
                $config = json_decode($callActivity->getProperty('config'), true);
                $startId = is_array($config) && isset($config['startEvent']) ? $config['startEvent'] : null;
                $instance = $this->callSubprocess($token, $startId);
                $this->getRepository()
                    ->getTokenRepository()
                    ->persistCallActivityActivated($token, $instance, $startId);
                $this->linkProcesses($token, $instance);
                $this->synchronizeInstances($token->getInstance(), $instance);
            }
        );
    }

    /**
     * Call the subprocess
     *
     * @return ExecutionInstanceInterface
     */
    protected function callSubprocess(TokenInterface $token, $startId)
    {
        // The entire data model is sent to the target
        $dataManager = new DataManager();
        $data = $dataManager->getData($token);

        // Add info about parent (Note MultiInstance also adds _parent info)
        if (!isset($data['_parent'])) {
            $data['_parent'] = [];
        }

        $data['_parent']['process_id'] = $token->getInstance()->process_id;
        $data['_parent']['request_id'] = $token->getInstance()->id;
        $data['_parent']['node_id'] = $token->element_id;

        $configString = $this->getProperty('config');
        if ($configString) {
            $config = json_decode($configString, true);
            $data['_parent']['config'] = $config;
        }
        $callable = $this->getCalledElement($data);
        $dataStore = $callable->getRepository()->createDataStore();

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
        // Copy data from subprocess to main process
        $data = $closedInstance->getDataStore()->getData();
        $dataManager = new DataManager();
        $dataManager->updateData($token, $data);
        $token->getInstance()->getProcess()->getEngine()->runToNextState();

        // Complete the sub process call
        $this->completeSubprocessBase($token);
        $this->synchronizeInstances($instance, $token->getInstance());

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
        // Log subprocess error message
        $message = [];
        if ($error) {
            $message = [$error->getName()];
        }
        if ($instance->errors && is_array($instance->errors)) {
            foreach ($instance->errors as $err) {
                $errorMessage = $err['message'];
                if (array_key_exists('body', $err)) {
                    // add the body but not the stack trace:
                    $errorMessage = "\n" . explode('Stack trace', $err['body'])[0];
                }
                $message[] = $errorMessage;
            }
        }
        $token->getInstance()->logError(new Exception(implode("\n", $message)), $this);

        $this->synchronizeInstances($instance, $token->getInstance());

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
    public function getCalledElement(array $data = [])
    {
        $calledElementRef = $this->getProperty(CallActivityInterface::BPMN_PROPERTY_CALLED_ELEMENT);
        $refs = explode('-', $calledElementRef);
        if (count($refs) === 1) {
            return $this->getOwnerDocument()->getElementInstanceById($calledElementRef);
        } elseif (count($refs) === 2) {
            // Capability to reuse other processes inside a process
            $process = is_numeric($refs[1]) ? Process::findOrFail($refs[1]) : Process::where('package_key', $refs[1])->firstOrFail();
            $engine = $this->getProcess()->getEngine();
            if ($this->subProcessRequestVersion) {
                $definitions = $engine->getDefinition($this->subProcessRequestVersion);
            } else {
                $definitions = $engine->getDefinition($process->getPublishedVersion($data));
            }
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
            // Set subprocess request (to get the right process version)
            $this->subProcessRequestVersion = $token->subProcessRequest->processVersion;
            $subprocess = $this->getProcess()->getEngine()->loadProcessRequest($token->subProcessRequest);
            $this->linkProcesses($token, $subprocess);
        }

        return $this->addTokenBase($instance, $token);
    }

    /**
     * Synchronize two process instances
     *
     * @param ExecutionInstanceInterface $instance
     * @param ExecutionInstanceInterface $currentInstance
     */
    private function synchronizeInstances(ExecutionInstanceInterface $instance, ExecutionInstanceInterface $currentInstance)
    {
        $parentProcessId = $instance->getProcess()->getOwnerDocument()->getModel()?->id;
        $childProcessId = $currentInstance->getProcess()->getOwnerDocument()->getModel()?->id;
        if ($parentProcessId !== $childProcessId) {
            $currentInstance->getProcess()->getEngine()->runToNextState();
        }
    }

    /**
     * Returns true if callable element is external to the owner definition
     *
     * @return bool
     */
    public function isFromExternalDefinition()
    {
        $ref = explode('-', $this->getProperty(CallActivityInterface::BPMN_PROPERTY_CALLED_ELEMENT));

        return count($ref) === 2 && is_numeric($ref[1]);
    }

    /**
     * Returns true if callable element is a service sub process (like data-connector)
     *
     * @return bool
     */
    public function isServiceSubProcess()
    {
        $ref = explode('-', $this->getProperty(CallActivityInterface::BPMN_PROPERTY_CALLED_ELEMENT));

        return count($ref) === 2 && !is_numeric($ref[1]);
    }
}
