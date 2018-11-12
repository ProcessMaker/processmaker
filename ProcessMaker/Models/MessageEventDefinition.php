<?php

namespace ProcessMaker\Models;

use ProcessMaker\Nayra\Bpmn\Models\MessageEventDefinition as Base;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowNodeInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

/**
 * Implementation of the message element.
 *
 */
class MessageEventDefinition extends Base
{

    /**
     * Implement the event definition behavior when an event is triggered.
     *
     * @param EventDefinitionInterface $event
     * @param FlowNodeInterface $target
     * @param ExecutionInstanceInterface|null $targetRequest
     * @param TokenInterface|null $token
     *
     * @return $this
     */
    public function execute(EventDefinitionInterface $event, FlowNodeInterface $target, ExecutionInstanceInterface $targetRequest = null, TokenInterface $token = null)
    {
        $sourceRequest = $token->processRequest;
        $payloadData = $this->getPayload()->getData($sourceRequest);
        $storage = $targetRequest->getDataStore();
        $data = $storage->getData();
        foreach ($payloadData as $key => $value) {
            $data[$key] = $value;
        }
        $storage->setData($data);
        return $this;
    }
}
