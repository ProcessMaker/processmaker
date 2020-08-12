<?php

namespace ProcessMaker\Models;

use ProcessMaker\Nayra\Bpmn\Models\MessageEventDefinition as Base;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowNodeInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Models\ProcessCollaboration;

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
        $sourceRequest = $token->getInstance();
        $payloadData = $this->getPayload()->getData($sourceRequest);
        $storage = $targetRequest->getDataStore();
        foreach ($payloadData as $key => $value) {
            $storage->putData($key, $value);
        }

        // Set collaboration
        $parent = $token->getInstance();
        $child = $targetRequest;

        if ($parent && $child) {
            if (!$parent->process_collaboration_id) {
                $collaboration = new ProcessCollaboration();
                $collaboration->process_id = $parent->process->getKey();
                $collaboration->saveOrFail();

                $parent->process_collaboration_id = $collaboration->getKey();
                $parent->saveOrFail();
            }
            $child->process_collaboration_id = $parent->process_collaboration_id;
            $child->saveOrFail();
        }
        
        return $this;
    }
}
